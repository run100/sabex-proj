<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesTradeTables
{
    private function createTradeTables(): void
    {
        foreach ([
            'seo_trade_user_stats',
            'seo_trade_user_blocks',
            'seo_trade_reports',
            'seo_trade_notifications',
            'seo_trade_events',
            'seo_trade_confirmations',
            'seo_trade_join_requests',
            'seo_trade_listing_item_traits',
            'seo_trade_listing_items',
            'seo_trade_listings',
            'seo_trade_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('seo_trade_users', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('roblox_sub', 64)->unique();
            $table->string('username', 100);
            $table->string('display_name', 100)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('profile_url', 500)->nullable();
            $table->string('account_status', 16)->default('active');
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_trade_listings', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->unsignedBigInteger('owner_user_id');
            $table->unsignedBigInteger('counterparty_user_id')->nullable();
            $table->string('status', 32)->default('open');
            $table->string('result_snapshot', 8)->default('na');
            $table->decimal('offering_value_snapshot', 20, 4)->default(0);
            $table->decimal('looking_value_snapshot', 20, 4)->default(0);
            $table->decimal('value_difference_snapshot', 20, 4)->default(0);
            $table->decimal('difference_percent_snapshot', 12, 4)->nullable();
            $table->string('note', 280)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('pending_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_trade_listing_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->string('side', 16);
            $table->unsignedTinyInteger('slot_no');
            $table->unsignedBigInteger('seo_item_id');
            $table->string('slug_snapshot', 190);
            $table->string('brainrot_name_snapshot', 190);
            $table->string('image_url_snapshot', 500)->nullable();
            $table->unsignedBigInteger('seo_item_variant_id')->nullable();
            $table->string('mutation_name_snapshot', 190)->nullable();
            $table->decimal('base_value_snapshot', 20, 4)->default(0);
            $table->decimal('mutation_value_multiplier_snapshot', 12, 6)->default(1);
            $table->decimal('trait_value_multiplier_snapshot', 12, 6)->default(1);
            $table->decimal('final_value_snapshot', 20, 4)->default(0);
            $table->decimal('base_income_snapshot', 30, 4)->nullable();
            $table->decimal('final_income_snapshot', 30, 4)->nullable();
            $table->string('demand_snapshot', 50)->nullable();
            $table->unsignedBigInteger('exist_count_snapshot')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('seo_trade_listing_item_traits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('listing_item_id');
            $table->string('trait_name', 100);
            $table->string('trait_name_snapshot', 190);
            $table->decimal('value_multiplier_snapshot', 12, 6)->default(1);
            $table->decimal('income_multiplier_snapshot', 12, 6)->default(1);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('seo_trade_join_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('requester_user_id');
            $table->unsignedBigInteger('owner_user_id');
            $table->string('status', 24)->default('requested');
            $table->string('note', 280)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
        });
        Schema::create('seo_trade_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('user_id');
            $table->string('confirmation', 16);
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['listing_id', 'user_id']);
        });
        Schema::create('seo_trade_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('event_type', 64);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('seo_trade_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 64);
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->unsignedBigInteger('join_request_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('title', 190);
            $table->string('message', 500)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('seo_trade_reports', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->unsignedBigInteger('reporter_user_id');
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->unsignedBigInteger('reported_user_id')->nullable();
            $table->string('reason', 32);
            $table->string('description', 1000)->nullable();
            $table->string('status', 16)->default('open');
            $table->string('resolution_note', 1000)->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('seo_trade_user_blocks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('blocked_user_id');
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'blocked_user_id']);
        });
        Schema::create('seo_trade_user_stats', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedInteger('trades_posted')->default(0);
            $table->unsignedInteger('trades_joined')->default(0);
            $table->unsignedInteger('trades_accepted')->default(0);
            $table->unsignedInteger('trades_completed')->default(0);
            $table->unsignedInteger('trades_failed')->default(0);
            $table->unsignedInteger('trades_disputed')->default(0);
            $table->decimal('completion_rate', 8, 4)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
