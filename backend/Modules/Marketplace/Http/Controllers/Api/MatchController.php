<?php

namespace Modules\Marketplace\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ai\Services\AiUsageService;
use Modules\Marketplace\Entities\MatchSetting;
use Modules\Marketplace\Entities\Opportunity;
use Modules\Marketplace\Services\MatchService;
use Modules\Profile\Entities\Profile;

/**
 * مطابقة الباحث لنفسه: «لماذا أنا مطابق؟» — يشرح ملاءمة المستخدم الحاليّ لفرصة
 * موصوفة بسماتها (عنوان/قطاع/مهارات)، مستقلًّا عن كون الفرصة مخزّنة (تعمل مع فرص الواجهة).
 */
class MatchController extends Controller
{
    public function __construct(
        private readonly MatchService $service,
        private readonly AiUsageService $usage,
    ) {}

    public function whyMatch(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
        ]);

        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();

        // فرصة عابرة (غير مخزّنة) من سمات الطلب — explain يقرأ العنوان/القطاع/المهارات فقط.
        $opp = new Opportunity([
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'skills' => $data['skills'] ?? [],
        ]);

        $ex = $this->service->explain($profile, $opp, MatchSetting::current(), $this->service->aiActive());

        if (($ex['live'] ?? false) && $user) {
            $this->usage->record(
                $user,
                (int) data_get($ex, 'meta.usage.request', 0),
                (int) data_get($ex, 'meta.usage.response', 0),
                data_get($ex, 'meta.provider'),
                data_get($ex, 'meta.model'),
            );
        }

        return $this->dataResponse([
            'live' => $ex['live'],
            'score' => $ex['score'],
            'verdict' => $ex['verdict'],
            'reasons' => $ex['reasons'],
            'redFlags' => $ex['redFlags'],
            'summary' => $ex['summary'],
            'matchedSkills' => $ex['matchedSkills'],
            'meta' => $ex['meta'],
        ]);
    }
}
