<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('method', 10);                    // POST | PUT | PATCH | DELETE
            $table->string('resource')->nullable()->index(); // plans | roles | platform-accounts | ...
            $table->string('action')->index();               // create | update | delete | close | approve | ...
            $table->string('path');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
