<?php

namespace Modules\Chat\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Chat\Entities\ChatSetting;
use Modules\Chat\Entities\DirectMessage;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        ChatSetting::query()->updateOrCreate(['id' => 1], [
            'direct_messages_enabled' => true,
            'assistant_enabled' => true,
            'moderation_enabled' => false,
            'retention_days' => 0,
        ]);

        // محادثات نموذجيّة للإشراف (لا تُنشأ إن وُجدت رسائل سلفًا).
        if (DirectMessage::query()->exists()) {
            return;
        }

        $threads = [
            [
                'a' => ['id' => 'seed-recruiter-1', 'name' => 'شركة أفق للتقنية'],
                'b' => ['id' => 'seed-seeker-1', 'name' => 'سارة العتيبي'],
                'msgs' => [
                    ['from' => 'a', 'body' => 'مرحبًا سارة، اطّلعنا على ملفّك وأعجبتنا خبرتك في تطوير الواجهات.'],
                    ['from' => 'b', 'body' => 'أهلًا بكم، شكرًا لتواصلكم. يسعدني معرفة تفاصيل الفرصة.'],
                    ['from' => 'a', 'body' => 'الدور «مطوّر واجهات أول» عن بُعد. هل يناسبك إجراء مقابلة الأسبوع القادم؟'],
                    ['from' => 'b', 'body' => 'نعم يناسبني تمامًا. يوم الثلاثاء مثاليّ لي.'],
                ],
            ],
            [
                'a' => ['id' => 'seed-seeker-2', 'name' => 'خالد المطيري'],
                'b' => ['id' => 'seed-interviewer-1', 'name' => 'م. ريم القحطاني (مقيّمة)'],
                'msgs' => [
                    ['from' => 'a', 'body' => 'السلام عليكم، أودّ حجز جلسة تقييم لمهارات إدارة المشاريع.'],
                    ['from' => 'b', 'body' => 'وعليكم السلام، بالتأكيد. أرسل لي سيرتك ونحدّد موعدًا.'],
                    ['from' => 'a', 'body' => 'تم الإرسال، بانتظار ردّكم.'],
                ],
            ],
            [
                'a' => ['id' => 'seed-org-2', 'name' => 'مؤسّسة نماء'],
                'b' => ['id' => 'seed-seeker-3', 'name' => 'نورة الشمري'],
                'msgs' => [
                    ['from' => 'a', 'body' => 'نورة، لديكِ فرصة تدريب منتهٍ بالتوظيف. مهتمّة؟'],
                    ['from' => 'b', 'body' => 'مهتمّة جدًّا! ما المتطلّبات؟'],
                ],
            ],
        ];

        $t = Carbon::now()->subDays(6);
        foreach ($threads as $thread) {
            foreach ($thread['msgs'] as $i => $m) {
                $from = $thread[$m['from']];
                $to = $thread[$m['from'] === 'a' ? 'b' : 'a'];
                $at = (clone $t)->addHours($i * 3);
                DirectMessage::create([
                    'sender_id' => $from['id'],
                    'sender_name' => $from['name'],
                    'recipient_id' => $to['id'],
                    'recipient_name' => $to['name'],
                    'body' => $m['body'],
                    'read_at' => $m['from'] === 'a' ? $at : null,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
            $t = $t->addDays(2);
        }
    }
}
