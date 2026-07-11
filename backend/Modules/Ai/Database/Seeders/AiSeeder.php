<?php

namespace Modules\Ai\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ai\Entities\AiCapability;
use Modules\Ai\Entities\AiKnowledge;
use Modules\Ai\Entities\AiSetting;

class AiSeeder extends Seeder
{
    public function run(): void
    {
        // ═══ الإعدادات المفردة ═══
        AiSetting::query()->updateOrCreate(['id' => 1], [
            'enabled' => true,
            'provider' => 'simulation',
            'model' => 'claude-opus-4-8',
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'language' => 'ar',
            'system_prompt' => 'أنت مساعد ذكيّ داخل منظومة توظيف. ساعد المستخدمين (باحثين، منشآت، مقيّمين) '
                .'بدقّة واستنادًا إلى بياناتهم الفعليّة، واحترم خصوصيّة المرشّحين ومعايير التوظيف العادل.',
            'assistant_level' => 2,
            'allow_user_level_override' => true,
            'doc_max_reads' => 3,
            'level_tokens' => ['1' => 600, '2' => 1200, '3' => 2400],
            'plan_quotas' => [
                'free' => ['maxTokensPerRequest' => 1024, 'dailyTokens' => 20000, 'weeklyTokens' => 100000, 'monthlyTokens' => 300000],
                'pro' => ['maxTokensPerRequest' => 2048, 'dailyTokens' => 80000, 'weeklyTokens' => 400000, 'monthlyTokens' => 1500000],
                'elite' => ['maxTokensPerRequest' => 4096, 'dailyTokens' => 0, 'weeklyTokens' => 0, 'monthlyTokens' => 0],
            ],
        ]);

        // ═══ أقسام الذكاء (ميزات المنصّة المدعومة بالذكاء) ═══
        $capabilities = [
            ['key' => 'candidate_matching', 'label' => 'مطابقة المرشّحين', 'icon' => 'mdi-account-search-outline', 'hint' => 'ترتيب المرشّحين للفرص وفق ملاءمة المهارات والقطاع', 'sort' => 0],
            ['key' => 'cv_screening', 'label' => 'فرز السير الذاتيّة', 'icon' => 'mdi-file-search-outline', 'hint' => 'تلخيص السير واستخلاص المهارات ونقاط القوّة', 'sort' => 1],
            ['key' => 'interview_assistant', 'label' => 'مساعد المقابلات', 'icon' => 'mdi-account-voice', 'hint' => 'اقتراح أسئلة مقابلة ومحاور تقييم حسب الدور', 'sort' => 2],
            ['key' => 'jd_generator', 'label' => 'توليد الوصف الوظيفيّ', 'icon' => 'mdi-text-box-plus-outline', 'hint' => 'صياغة أوصاف وظيفيّة ومتطلّبات من مدخلات موجزة', 'sort' => 3],
            ['key' => 'survey_insights', 'label' => 'تحليل الاستبيانات', 'icon' => 'mdi-chart-box-outline', 'hint' => 'استخلاص رؤى من ردود الاستبيانات', 'sort' => 4],
            ['key' => 'chat_assistant', 'label' => 'المساعد المحادثيّ', 'icon' => 'mdi-robot-happy-outline', 'hint' => 'مساعد محادثة داخل المنصّة يجيب من بيانات المستخدم', 'sort' => 5],
            ['key' => 'recommendations', 'label' => 'التوصيات الذكيّة', 'icon' => 'mdi-lightbulb-on-outline', 'hint' => 'توصيات فرص/خبراء/خطوات تمكينيّة مخصّصة', 'sort' => 6],
        ];
        foreach ($capabilities as $c) {
            AiCapability::query()->updateOrCreate(['key' => $c['key']], $c);
        }

        // ═══ قاعدة المعرفة (بذور) ═══
        $knowledge = [
            ['title' => 'معايير التوظيف العادل', 'content' => 'لا تُبنى التوصيات على العرق أو الجنس أو العمر؛ التركيز على المهارات والخبرة والملاءمة الفعليّة للدور.', 'tags' => ['حوكمة', 'عدالة'], 'enabled' => true],
            ['title' => 'أسلوب المنصّة', 'content' => 'خاطب المستخدم بلباقة ومهنيّة، بالعربيّة الفصحى المبسّطة، مع أمثلة عمليّة مربوطة ببياناته.', 'tags' => ['أسلوب'], 'enabled' => true],
        ];
        foreach ($knowledge as $k) {
            AiKnowledge::query()->updateOrCreate(['title' => $k['title']], $k);
        }
    }
}
