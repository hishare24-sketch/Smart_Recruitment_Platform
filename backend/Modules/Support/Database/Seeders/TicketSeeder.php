<?php

namespace Modules\Support\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Support\Entities\Ticket;

class TicketSeeder extends Seeder
{
    /** تذاكر دعم نموذجيّة مع ردود. */
    public function run(): void
    {
        $samples = [
            ['user_name' => 'خالد العتيبي', 'subject' => 'لم يُخصم رصيد الترقية بشكل صحيح', 'category' => 'billing', 'priority' => 'high', 'status' => 'open',
                'replies' => [['خالد العتيبي', false, 'رقّيتُ للباقة الاحترافية لكن خُصم مبلغ مختلف.']]],
            ['user_name' => 'ريم الدوسري', 'subject' => 'لا أستطيع رفع صورة الملف الشخصيّ', 'category' => 'technical', 'priority' => 'normal', 'status' => 'pending',
                'replies' => [['ريم الدوسري', false, 'تظهر رسالة خطأ عند الرفع.'], ['فريق الدعم', true, 'شكرًا لك، نعمل على المشكلة. ما نوع الملف؟']]],
            ['user_name' => 'سلمان الحربي', 'subject' => 'طلب حذف الحساب', 'category' => 'account', 'priority' => 'low', 'status' => 'resolved',
                'replies' => [['سلمان الحربي', false, 'أرغب بحذف حسابي.'], ['فريق الدعم', true, 'تمّت معالجة طلبك.']]],
            ['user_name' => 'نورة المطيري', 'subject' => 'استفسار عن مزايا باقة النخبة', 'category' => 'other', 'priority' => 'normal', 'status' => 'open',
                'replies' => [['نورة المطيري', false, 'ما الفرق بين الاحترافية والنخبة؟']]],
            ['user_name' => 'فهد', 'subject' => 'الموقع بطيء جدًّا اليوم', 'category' => 'technical', 'priority' => 'urgent', 'status' => 'open',
                'replies' => [['فهد', false, 'الصفحات تستغرق وقتًا طويلًا للتحميل.']]],
        ];

        foreach ($samples as $s) {
            $ticket = Ticket::firstOrCreate(
                ['subject' => $s['subject']],
                [
                    'user_name' => $s['user_name'], 'category' => $s['category'],
                    'priority' => $s['priority'], 'status' => $s['status'],
                    'last_reply_at' => Carbon::now(),
                ],
            );
            if ($ticket->replies()->exists()) {
                continue;
            }
            foreach ($s['replies'] as [$author, $isStaff, $body]) {
                $ticket->replies()->create(['author_name' => $author, 'is_staff' => $isStaff, 'body' => $body]);
            }
        }
    }
}
