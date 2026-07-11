<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // حوكمة المحادثات — صفّ مفرد (singleton). يحكم ميزة الرسائل المباشرة والمساعد الذكيّ.
        Schema::create('chat_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('direct_messages_enabled')->default(true);  // يحكم إرسال الرسائل المباشرة فعليًّا
            $table->boolean('assistant_enabled')->default(true);        // إتاحة المساعد الذكيّ (مع حوكمة الذكاء)
            $table->boolean('moderation_enabled')->default(false);      // إشراف/رصد الرسائل
            $table->unsignedSmallInteger('retention_days')->default(0); // 0 = بلا حذف
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_settings');
    }
};
