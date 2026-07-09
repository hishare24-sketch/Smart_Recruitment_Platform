<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // نوع الحساب: فرد/منشأة — سمة عرض غير مقيّدة (لا تحجب أي دور)
            if (! Schema::hasColumn('users', 'kind')) {
                $table->string('kind')->default('individual')->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('kind');
        });
    }
};
