<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إعدادات الذكاء — صفّ مفرد (singleton, id=1) يجمع تهيئة المزوّد والحصص ومستويات المساعد.
        Schema::create('ai_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('provider')->default('simulation'); // simulation | claude | openai | custom
            $table->string('model')->nullable();
            $table->text('api_key')->nullable();               // للمزوّد المخصّص فقط (claude/openai مفاتيحها في env)
            $table->string('endpoint')->nullable();            // للمزوّد المخصّص
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->unsignedInteger('max_tokens')->default(2048);
            $table->string('language')->default('ar');          // ar | en | auto
            $table->text('system_prompt')->nullable();
            $table->unsignedTinyInteger('assistant_level')->default(2); // 1|2|3 الافتراضيّ الإداريّ
            $table->boolean('allow_user_level_override')->default(true);
            $table->unsignedSmallInteger('doc_max_reads')->default(3);
            $table->json('level_tokens')->nullable();           // {"1":600,"2":1200,"3":2400}
            $table->json('plan_quotas')->nullable();            // {"free":{maxTokensPerRequest,dailyTokens,weeklyTokens,monthlyTokens},...}
            $table->timestamps();
        });

        // أقسام الذكاء — مفاتيح تفعيل لكلّ ميزة مدعومة بالذكاء (مطابقة/فرز/مساعد مقابلة…).
        Schema::create('ai_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('hint')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // قاعدة المعرفة — مقاطع سياق يحقنها المساعد (سياسات التوظيف، أسلوب المنصّة…).
        Schema::create('ai_knowledge', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->json('tags')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge');
        Schema::dropIfExists('ai_capabilities');
        Schema::dropIfExists('ai_settings');
    }
};
