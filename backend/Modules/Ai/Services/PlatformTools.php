<?php

namespace Modules\Ai\Services;

use Modules\Marketplace\Entities\Application;
use Modules\Marketplace\Entities\Opportunity;
use Modules\User\Entities\User;

/**
 * أدوات المساعد للوصول لبيانات المنصّة الحيّة (function-calling، للقراءة فقط).
 * كلّ أداة تُعرّف مخطّطًا (input_schema) وتُنفَّذ في سياق المستخدم الحاليّ، وأيّ فشل
 * يُلتقَط ويُعاد كـ {error} كي لا تنكسر حلقة الأدوات في المزوّد.
 */
class PlatformTools
{
    /** تعريفات الأدوات المتاحة للمزوّد (اسم/وصف/مخطّط مدخلات). */
    public function definitions(): array
    {
        return [
            [
                'name' => 'get_my_applications',
                'description' => 'يعيد تقديمات المستخدم الحاليّ على الفرص (عنوان الفرصة، الشركة، المرحلة). استخدمه حين يسأل عن حالة تقديماته أو تقدّمه.',
                'schema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
            ],
            [
                'name' => 'search_opportunities',
                'description' => 'يبحث في الفرص المنشورة على المنصّة اختياريًّا بكلمة مفتاحيّة و/أو قطاع، ويعيد عناوينها وشركاتها وقطاعاتها. استخدمه حين يسأل عن فرص متاحة أو مناسبة له.',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => 'كلمة بحث في العنوان أو الشركة (اختياريّة)'],
                        'category' => ['type' => 'string', 'description' => 'كود القطاع لتصفية النتائج (اختياريّ)'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /** ينفّذ أداة باسمها في سياق المستخدم — يعيد نتيجة قابلة للتسلسل أو {error}. */
    public function execute(string $name, array $input, User $user): array
    {
        try {
            return match ($name) {
                'get_my_applications' => $this->myApplications($user),
                'search_opportunities' => $this->searchOpportunities($input),
                default => ['error' => 'أداة غير معروفة: '.$name],
            };
        } catch (\Throwable $e) {
            return ['error' => 'تعذّر تنفيذ الأداة: '.$e->getMessage()];
        }
    }

    private function myApplications(User $user): array
    {
        $rows = Application::where('user_id', $user->id)
            ->with('opportunity:id,title,company')
            ->orderByDesc('id')->limit(20)->get();

        return [
            'count' => $rows->count(),
            'applications' => $rows->map(fn (Application $a) => [
                'opportunity' => $a->opportunity?->title ?? '—',
                'company' => $a->opportunity?->company,
                'stage' => $a->stage,
            ])->values()->all(),
        ];
    }

    private function searchOpportunities(array $input): array
    {
        $keyword = is_string($input['keyword'] ?? null) ? trim($input['keyword']) : '';
        $category = is_string($input['category'] ?? null) ? trim($input['category']) : '';

        $rows = Opportunity::query()
            ->when($keyword !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$keyword}%")->orWhere('company', 'like', "%{$keyword}%")))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->orderByDesc('id')->limit(15)->get(['id', 'title', 'company', 'category']);

        return [
            'count' => $rows->count(),
            'opportunities' => $rows->map(fn (Opportunity $o) => [
                'title' => $o->title,
                'company' => $o->company,
                'category' => $o->category,
            ])->values()->all(),
        ];
    }
}
