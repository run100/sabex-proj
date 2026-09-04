<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Review only. Do not run until confirmed.
 * Manual SQL: database/schema/trades.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_users', function (Blueprint $table): void {
            $table->id();
            $table->string('roblox_sub', 64)->unique();
            $table->string('username')->default('');
            $table->string('display_name')->default('');
            $table->string('avatar_url')->default('');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trade_posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('trade_user_id');
            $table->string('status', 16)->default('open');
            $table->json('offer_json');
            $table->json('receive_json');
            $table->unsignedInteger('offer_value')->default(0);
            $table->unsignedInteger('receive_value')->default(0);
            $table->string('wfl', 8)->default('fair');
            $table->string('note', 280)->default('');
            $table->timestamps();
            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_posts');
        Schema::dropIfExists('trade_users');
    }
};
