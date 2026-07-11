<?php

namespace Modules\Account\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Account\Entities\Plan;

class PlanSeeder extends Seeder
{
    /** كتالوج الباقات — الأسعار مطابقة لـ AccountService::PLAN_PRICE (0/50/150). */
    public function run(): void
    {
        $plans = [
            [
                'key' => 'free', 'name' => 'الأساسية', 'price' => 0, 'survey_limit' => 3, 'sort' => 0, 'active' => true,
                'features' => [
                    'كل الأدوار المهنية فورًا (باحث/جهة/مقيّم/مرشد/مدرب/مستشار/مُزكٍّ)',
                    'المركز الموحّد والمحفظة والتحليلات الموحّدة',
                    'الصفحة التعريفية: القصة والمهارات والمصداقية',
                    '3 استبيانات نشطة',
                ],
            ],
            [
                'key' => 'pro', 'name' => 'الاحترافية', 'price' => 50, 'survey_limit' => 10, 'sort' => 1, 'active' => true,
                'features' => [
                    'الصفحة التعريفية: إنجازات وخبرات ومعرض أعمال وتوصيات وشارات أدوار',
                    'ثيم مخصص بلونك الخاص لصفحتك التعريفية',
                    '10 استبيانات نشطة + الإدارة الاحترافية الكاملة',
                    'إدارة الحسابات المفوَّضة (شركات وفرق)',
                ],
            ],
            [
                'key' => 'elite', 'name' => 'النخبة', 'price' => 150, 'survey_limit' => null, 'sort' => 2, 'active' => true,
                'features' => [
                    'التفاعل الاجتماعي الكامل: متابعون وتقييم وتعليقات على صفحتك',
                    'استبيانات بلا حدود',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['key' => $plan['key']], $plan);
        }
    }
}
