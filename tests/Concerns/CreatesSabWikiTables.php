<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesSabWikiTables
{
    private function createSeoTables(): void
    {
        foreach ([
            'seo_item_observations',
            'seo_item_current_values',
            'seo_item_translations',
            'seo_item_variants',
            'seo_items',
            'seo_news_articles',
            'seo_access_logs',
            'seo_value_sources',
            'seo_games',
            'seo_sites',
            'seo_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('seo_sites', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->string('name')->nullable();
            $table->string('domain')->nullable();
            $table->string('base_url')->nullable();
            $table->string('output_path')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_games', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_site_id')->nullable();
            $table->string('slug');
            $table->string('name')->nullable();
            $table->string('source_url')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_value_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->string('name')->nullable();
            $table->string('url')->nullable();
            $table->string('parser_type')->nullable();
            $table->integer('priority')->nullable();
            $table->boolean('is_primary_source')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('seo_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_game_id')->nullable();
            $table->string('slug');
            $table->string('name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('rarity')->nullable();
            $table->text('description')->nullable();
            $table->text('summary')->nullable();
            $table->boolean('is_publish_html')->default(true);
            $table->boolean('is_listed')->default(true);
            $table->integer('total_exists')->nullable();
            $table->integer('exist_estimate_low')->nullable();
            $table->integer('exist_estimate_high')->nullable();
            $table->string('exist_estimate_reason')->nullable();
            $table->string('exist_estimate_confidence')->nullable();
            $table->timestamp('exist_estimate_collected_at')->nullable();
            $table->string('image_url')->default('');
            $table->string('local_image_url')->default('');
            $table->string('avg_coins_raw')->default('');
            $table->json('attributes_json')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('seo_item_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_item_id');
            $table->string('locale');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_item_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_item_id');
            $table->string('variant_key');
            $table->string('variant_name')->nullable();
            $table->string('variant_type')->nullable();
            $table->string('mutation_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('attributes_json')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_item_current_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_item_variant_id');
            $table->unsignedBigInteger('seo_value_source_id');
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->string('exist_count_raw')->default('');
            $table->integer('exist_count_normalized')->nullable();
            $table->string('value_raw')->nullable();
            $table->decimal('value_normalized', 16, 4)->nullable();
            $table->string('currency')->nullable();
            $table->string('demand')->nullable();
            $table->integer('confidence')->nullable();
            $table->string('source_payload_hash')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_item_observations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_item_variant_id');
            $table->unsignedBigInteger('seo_value_source_id');
            $table->unsignedBigInteger('seo_sync_run_id')->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->string('exist_count_raw')->default('');
            $table->string('value_raw')->nullable();
            $table->decimal('value_normalized', 16, 4)->nullable();
            $table->string('currency')->nullable();
            $table->string('demand')->nullable();
            $table->integer('confidence')->nullable();
            $table->string('source_payload_hash')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name', 100);
            $table->string('role', 32)->default('super_admin');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
        });
        Schema::create('seo_news_articles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_site_id');
            $table->integer('type')->default(200);
            $table->string('slug');
            $table->string('locale')->default('en');
            $table->string('title')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('body_html')->nullable();
            $table->string('status')->default('published');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_system_log')->default(false);
            $table->timestamps();
        });
        Schema::create('seo_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 32);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('subject_type', 32)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}
