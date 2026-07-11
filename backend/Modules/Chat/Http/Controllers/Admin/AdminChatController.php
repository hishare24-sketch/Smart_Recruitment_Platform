<?php

namespace Modules\Chat\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Ai\Entities\AiCapability;
use Modules\Ai\Entities\AiKnowledge;
use Modules\Ai\Entities\AiSetting;
use Modules\Chat\Entities\ChatSetting;
use Modules\Chat\Entities\DirectMessage;

class AdminChatController extends Controller
{
    /** التهيئة — حوكمة المحادثات + ربط حالة الذكاء الحيّة (من موديول Ai). */
    public function config()
    {
        $this->authorize('view_chat');

        return $this->dataResponse([
            'settings' => $this->settingsPayload(ChatSetting::current()),
            'aiLinkage' => $this->aiLinkage(),
        ]);
    }

    /** تحديث حوكمة المحادثات (جزئيّ) — إعدادات تحكم السلوك الحيّ. */
    public function updateSettings(Request $request)
    {
        $this->authorize('manage_chat');

        $data = $request->validate([
            'direct_messages_enabled' => ['sometimes', 'boolean'],
            'assistant_enabled' => ['sometimes', 'boolean'],
            'moderation_enabled' => ['sometimes', 'boolean'],
            'retention_days' => ['sometimes', 'integer', 'min:0', 'max:3650'],
        ]);

        $s = ChatSetting::current();
        $s->fill($data)->save();

        return $this->updatedResponse($this->settingsPayload($s->fresh()));
    }

    /** إشراف — قائمة المحادثات (أزواج المتحاورين) بترقيم + بحث + فرز بآخر نشاط. */
    public function threads(Request $request)
    {
        $this->authorize('view_chat');

        $q = trim((string) $request->query('q', ''));

        // اجمع الرسائل في محادثات بمفتاح زوج غير مرتّب.
        $threads = DirectMessage::query()->orderBy('created_at')->orderBy('id')->get()
            ->groupBy(fn (DirectMessage $m) => $this->pairKey($m->sender_id, $m->recipient_id))
            ->map(function ($msgs, $key) {
                $last = $msgs->last();
                $names = $msgs->flatMap(fn ($m) => [$m->sender_id => $m->sender_name, $m->recipient_id => $m->recipient_name]);

                return [
                    'key' => $key,
                    'participants' => $names->values()->unique()->take(2)->values()->all(),
                    'messagesCount' => $msgs->count(),
                    'lastBody' => Str::limit((string) $last->body, 80),
                    'lastSender' => $last->sender_name,
                    'lastMessageAt' => optional($last->created_at)->toISOString(),
                    'unread' => $msgs->whereNull('read_at')->count(),
                ];
            })
            ->values();

        if ($q !== '') {
            $threads = $threads->filter(fn ($t) => Str::contains(
                implode(' ', array_merge($t['participants'], [$t['lastBody']])), $q
            ))->values();
        }

        $threads = $threads->sortByDesc('lastMessageAt')->values();

        $perPage = (int) $request->query('perPage', 15);
        $page = max(1, (int) $request->query('page', 1));
        $slice = $threads->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator($slice, $threads->count(), $perPage, $page);

        return $this->dashboardResponse($paginator);
    }

    /** محادثة واحدة — كامل الرسائل بين الزوج (قراءة إشرافيّة فقط). */
    public function thread(Request $request, string $key)
    {
        $this->authorize('view_chat');

        $messages = DirectMessage::query()->orderBy('created_at')->orderBy('id')->get()
            ->filter(fn (DirectMessage $m) => $this->pairKey($m->sender_id, $m->recipient_id) === $key)
            ->map(fn (DirectMessage $m) => [
                'id' => $m->id,
                'senderId' => $m->sender_id,
                'senderName' => $m->sender_name,
                'body' => $m->body,
                'read' => $m->read_at !== null,
                'at' => optional($m->created_at)->toISOString(),
            ])->values();

        return $this->dataResponse([
            'key' => $key,
            'participants' => $messages->flatMap(fn ($m) => [$m['senderName']])->unique()->values()->all(),
            'messages' => $messages,
        ]);
    }

    /** إحصاءات — عدّادات + سلسلة نشاط 14 يومًا + أنشط المرسِلين. */
    public function stats()
    {
        $this->authorize('view_chat');

        $all = DirectMessage::get(['sender_id', 'recipient_id', 'sender_name', 'created_at']);

        $threads = $all->groupBy(fn (DirectMessage $m) => $this->pairKey($m->sender_id, $m->recipient_id))->count();
        $participants = $all->flatMap(fn ($m) => [$m->sender_id, $m->recipient_id])->unique()->count();
        $today = $all->filter(fn ($m) => optional($m->created_at)->isToday())->count();

        $raw = DirectMessage::where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $series[] = ['date' => $date, 'value' => (int) ($raw[$date] ?? 0)];
        }

        $topSenders = $all->groupBy('sender_name')->map->count()->sortDesc()->take(6)
            ->map(fn ($v, $k) => ['label' => $k ?: '—', 'value' => $v])->values();

        return $this->dataResponse([
            'threads' => $threads,
            'messages' => $all->count(),
            'activeToday' => $today,
            'participants' => $participants,
            'series' => $series,
            'topSenders' => $topSenders,
        ]);
    }

    /**
     * اختبار المساعد الذكيّ — ردّ محاكاة يحترم حوكمة الذكاء فعليًّا:
     * يُحجب إن أُوقف الذكاء أو قسم المحادثة أو مساعد الشات؛ ويتشكّل حسب المستوى ويحقن المعرفة.
     */
    public function assistantPreview(Request $request)
    {
        $this->authorize('view_chat');

        $data = $request->validate(['prompt' => ['required', 'string', 'max:1000']]);
        $prompt = trim($data['prompt']);

        $ai = AiSetting::current();
        $cap = AiCapability::where('key', 'chat_assistant')->first();
        $chat = ChatSetting::current();

        $reasons = [];
        if (! $ai->enabled) {
            $reasons[] = 'مفتاح الذكاء الرئيسيّ متوقّف';
        }
        if ($cap && ! $cap->enabled) {
            $reasons[] = 'قسم مساعد المحادثة في حوكمة الذكاء معطّل';
        }
        if (! $chat->assistant_enabled) {
            $reasons[] = 'المساعد الذكيّ متوقّف في حوكمة المحادثات';
        }
        if (! empty($reasons)) {
            return $this->forbiddenResponse('المساعد غير متاح: '.implode(' · ', $reasons));
        }

        $level = (int) $ai->assistant_level;
        $tokensCap = (int) ($ai->level_tokens[$level] ?? [1 => 600, 2 => 1200, 3 => 2400][$level] ?? 1200);
        $knowledge = AiKnowledge::where('enabled', true)->get(['title', 'content']);

        $reply = $this->composeReply($prompt, $level, $knowledge);

        return $this->dataResponse([
            'reply' => $reply,
            'level' => $level,
            'tokensCap' => $tokensCap,
            'provider' => $ai->provider,
            'model' => $ai->model,
            'simulated' => $ai->provider === 'simulation',
            'usedKnowledge' => $knowledge->pluck('title')->values()->all(),
        ]);
    }

    // ═══ مساعدات ═══

    private function settingsPayload(ChatSetting $s): array
    {
        return [
            'directMessagesEnabled' => $s->direct_messages_enabled,
            'assistantEnabled' => $s->assistant_enabled,
            'moderationEnabled' => $s->moderation_enabled,
            'retentionDays' => $s->retention_days,
        ];
    }

    /** حالة الذكاء الحيّة كما تحكم المحادثات — الجسر بين موديول Chat وموديول Ai. */
    private function aiLinkage(): array
    {
        $ai = AiSetting::current();
        $cap = AiCapability::where('key', 'chat_assistant')->first();
        $chat = ChatSetting::current();

        $capEnabled = $cap?->enabled ?? false;
        $effective = $ai->enabled && $capEnabled && $chat->assistant_enabled;

        return [
            'aiEnabled' => $ai->enabled,
            'chatCapabilityEnabled' => $capEnabled,
            'assistantEnabled' => $chat->assistant_enabled,
            'effectiveEnabled' => $effective,
            'provider' => $ai->provider,
            'model' => $ai->model,
            'assistantLevel' => (int) $ai->assistant_level,
        ];
    }

    /** مفتاح زوج غير مرتّب (a|b = b|a). */
    private function pairKey(string $x, string $y): string
    {
        $pair = [$x, $y];
        sort($pair);

        return implode('|', $pair);
    }

    /** يشكّل ردًّا (محاكاة) بحسب مستوى المساعد ويحقن المعرفة المفعّلة — عربيّ (لغة المنصّة الأساس). */
    private function composeReply(string $prompt, int $level, $knowledge): string
    {
        $intro = 'بخصوص رسالتك: «'.Str::limit($prompt, 120).'»';

        $core = match ($level) {
            1 => 'إجابة مباشرة: أستطيع أن أدلّك على أنسب فرصة أو خبير بناءً على بيانات ملفّك، بلا استطراد.',
            3 => 'تحليل معمّق: راجعتُ مقصد رسالتك، وأرصد أنسب الفرص لك وأيّ نقص في ملفّك، ثمّ أقترح خطواتٍ '
                .'عمليّة مرتّبة بالأولويّة عبر أقسام المنصّة (استكمال المهارات، توثيقها، التقديم على الفرص الملائمة)، '
                .'وأختم بسؤال متابعة: أيّ مسار تفضّل أن نبدأ به الآن؟',
            default => 'إجابة واضحة مستندة إلى بياناتك، مع نصيحة استباقيّة واحدة: أكمِل قسم المهارات في ملفّك لرفع درجة مطابقتك للفرص.',
        };

        $kb = '';
        if ($knowledge->count() > 0) {
            $kb = "\n\n".'مستندًا إلى معرفة المنصّة: '.$knowledge->pluck('title')->take(3)->join('، ');
        }

        return $intro."\n\n".$core.$kb;
    }
}
