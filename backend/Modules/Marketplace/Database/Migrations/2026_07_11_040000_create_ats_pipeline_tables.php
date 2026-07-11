<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مراحل التوظيف على التقديم — قلب نظام تتبّع المتقدّمين (ATS).
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('stage')->default('applied')->index();     // applied|screening|interview|offer|hired|rejected
            $table->text('note')->nullable();
            $table->timestamp('stage_changed_at')->nullable();
        });

        // سجلّ حركات المرشّح عبر المراحل (أثر تدقيق كامل لخطّ الأنابيب).
        Schema::create('application_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->index();
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->text('note')->nullable();
            $table->foreignId('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_events');
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['stage', 'note', 'stage_changed_at']);
        });
    }
};
