<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemObservation;
use App\Models\SeoItemVariant;
use App\Models\SeoSite;
use App\Models\SeoValueSource;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabRotCalculatorSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SabCalculatorRefreshTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeoTables();
        File::delete(SabRotCalculatorSyncService::calculatorMetaPath());
    }

    protected function tearDown(): void
    {
        File::delete(SabRotCalculatorSyncService::calculatorMetaPath());
        File::delete(storage_path('app/seo/sab-price-history/refresh-test-brainrot.json'));
        File::delete(storage_path('app/seo/sab-price-history/brand-new-brainrot.json'));
        parent::tearDown();
    }

    public function test_refresh_upserts_catalog_prices_meta_and_keeps_old_points(): void
    {
        $site = SeoSite::query()->create([
            'slug' => SabRenderService::SITE_SLUG,
            'name' => 'SAB',
            'settings_json' => [],
        ]);
        SeoGame::query()->create([
            'seo_site_id' => $site->id,
            'slug' => 'steal-a-brainrot',
            'name' => 'Steal a Brainrot',
        ]);
        SeoValueSource::query()->create([
            'slug' => SabRotCalculatorSyncService::SOURCE_SLUG,
            'name' => 'Rot Rocks Calculator',
            'url' => 'https://rot.rocks/trading/calculator',
            'parser_type' => 'json_api',
            'priority' => 80,
            'is_primary_source' => false,
            'enabled' => true,
        ]);
        $item = SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'refresh-test-brainrot',
            'name' => 'Refresh Test Brainrot',
            'is_listed' => true,
            'is_publish_html' => true,
            'attributes_json' => [
                'rot_rocks' => [
                    'first_seen_at' => '2026-01-01T00:00:00+00:00',
                ],
            ],
        ]);
        $base = SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'base',
            'variant_name' => 'Base',
            'variant_type' => 'base',
            'multiplier' => 1,
        ]);
        $rainbow = SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'mutation-rainbow',
            'variant_name' => 'Rainbow',
            'variant_type' => 'mutation',
            'mutation_name' => 'Rainbow',
            'multiplier' => 10,
            'attributes_json' => ['rot_id' => 'mutation-rainbow'],
        ]);

        $path = storage_path('app/seo/sab-price-history/refresh-test-brainrot.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'slug' => 'refresh-test-brainrot',
            'variants' => [
                (string) $base->id => [
                    ['date' => '2026-01-01', 'value' => 10],
                    ['date' => '2026-07-22', 'value' => 99],
                ],
                (string) $rainbow->id => [
                    ['date' => '2026-07-22', 'value' => 385],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES));

        File::put(SabRotCalculatorSyncService::calculatorMetaPath(), json_encode([
            'synced_at' => '2026-09-03T00:00:00+00:00',
            'traits' => [[
                'id' => 'old-trait',
                'name' => 'Old Trait',
                'multiplier' => 1,
                'valueMultiplier' => 1,
                'image' => null,
            ]],
            'mutations' => [],
            'streakMultipliers' => ['3' => 2, '6' => 3],
        ], JSON_UNESCAPED_SLASHES));

        Http::fake([
            'https://rot.rocks/api/brainrots' => Http::response([
                'brainrots' => [
                    [
                        'id' => 'brainrot-garama',
                        'name' => 'Refresh Test Brainrot',
                        'robuxValue' => 80,
                        'demand' => 'HIGH',
                        'baseIncome' => 12,
                        'mutationValues' => [[
                            'mutationId' => 'mutation-rainbow',
                            'robuxValue' => 340,
                            'demand' => 'HIGH',
                        ]],
                    ],
                    [
                        'id' => 'brainrot-new',
                        'name' => 'Brand New Brainrot',
                        'robuxValue' => 9,
                        'baseIncome' => 3,
                    ],
                ],
            ]),
            'https://rot.rocks/api/mutations' => Http::response([
                'mutations' => [[
                    'id' => 'mutation-rainbow',
                    'name' => 'Rainbow',
                    'multiplier' => 10,
                ]],
            ]),
            'https://rot.rocks/api/traits' => Http::response([
                'traits' => [[
                    'id' => 'trait-rainbow-balloon',
                    'name' => 'Rainbow Balloon',
                    'multiplier' => 6.5,
                    'valueMultiplier' => 1.8,
                ]],
                'streakMultipliers' => ['3' => 2, '6' => 4],
            ]),
        ]);

        $this->artisan('seo:sab-calculator-refresh')
            ->expectsOutput('SAB calculator refresh completed.')
            ->expectsOutput('Remote: brainrots=2 mutations=1 traits=1')
            ->expectsOutput('Items: processed=2 new=1')
            ->expectsOutput('New slugs: brand-new-brainrot')
            ->expectsOutput('Mutations: upserted=1 new=0')
            ->expectsOutput('Current values: 3 changed=3 unchanged=0')
            ->expectsOutput('Observations: 3')
            ->expectsOutput('JSON files: 2')
            ->expectsOutput('Traits: 1 new=1')
            ->expectsOutput('Images downloaded: 0')
            ->assertSuccessful();

        $this->assertSame(2, SeoItem::query()->count());
        $created = SeoItem::query()->where('slug', 'brand-new-brainrot')->first();
        $this->assertNotNull($created);
        $this->assertTrue((bool) $created->is_listed);
        $this->assertFalse((bool) $created->is_publish_html);
        $this->assertSame(80.0, (float) SeoItemCurrentValue::query()->where('seo_item_variant_id', $base->id)->value('value_normalized'));
        $this->assertSame(1, SeoItemObservation::query()->where('seo_item_variant_id', $base->id)->count());

        $payload = json_decode((string) File::get($path), true);
        $this->assertSame('2026-01-01', $payload['variants'][(string) $base->id][0]['date']);
        $this->assertSame(10, $payload['variants'][(string) $base->id][0]['value']);
        $this->assertSame(now()->toDateString(), $payload['variants'][(string) $base->id][2]['date']);
        $this->assertSame(80, $payload['variants'][(string) $base->id][2]['value']);
        $this->assertSame(385, $payload['variants'][(string) $rainbow->id][0]['value']);
        $this->assertSame(340, $payload['variants'][(string) $rainbow->id][1]['value']);

        $meta = json_decode((string) File::get(SabRotCalculatorSyncService::calculatorMetaPath()), true);
        $this->assertSame(now()->toIso8601String(), $meta['synced_at']);
        $this->assertSame('Rainbow Balloon', $meta['traits'][0]['name']);
        $this->assertSame(6.5, $meta['traits'][0]['multiplier']);
        $this->assertSame('Rainbow', $meta['mutations'][0]['name']);
        $this->assertSame(4, $meta['streakMultipliers']['6']);
        $this->assertSame(['3' => 2, '6' => 4], data_get($site->fresh()->settings_json, 'sab_calculator.streak_multipliers'));
        $this->assertSame('2026-01-01T00:00:00+00:00', data_get($item->fresh()->attributes_json, 'rot_rocks.first_seen_at'));

        $firstSyncedAt = $meta['synced_at'];
        $this->travel(2)->seconds();
        $this->artisan('seo:sab-calculator-refresh')
            ->expectsOutput('Items: processed=2 new=0')
            ->expectsOutput('Mutations: upserted=1 new=0')
            ->expectsOutput('Current values: 3 changed=0 unchanged=3')
            ->expectsOutput('Observations: 0')
            ->expectsOutput('Traits: 1 new=0')
            ->assertSuccessful();
        $this->assertSame(1, SeoItemObservation::query()->where('seo_item_variant_id', $base->id)->count());
        $this->assertSame(2, SeoItem::query()->count());

        $secondMeta = json_decode((string) File::get(SabRotCalculatorSyncService::calculatorMetaPath()), true);
        $this->assertNotSame($firstSyncedAt, $secondMeta['synced_at']);
        $this->assertSame('Rainbow Balloon', $secondMeta['traits'][0]['name']);
    }

    public function test_schedule_lists_sab_calculator_refresh(): void
    {
        $exitCode = Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('seo:sab-calculator-refresh', $output);
        $this->assertMatchesRegularExpression('/30\s+4\s+\*\s+\*\s+\*/', $output);
    }

    private function createSeoTables(): void
    {
        foreach ([
            'seo_item_observations',
            'seo_item_current_values',
            'seo_item_variants',
            'seo_items',
            'seo_value_sources',
            'seo_games',
            'seo_sites',
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
            $table->string('rarity')->nullable();
            $table->text('description')->nullable();
            $table->text('summary')->nullable();
            $table->boolean('is_listed')->default(false);
            $table->boolean('is_publish_html')->default(true);
            $table->string('avg_coins_raw')->nullable();
            $table->string('image_url')->default('');
            $table->string('local_image_url')->default('');
            $table->json('attributes_json')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('seo_item_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_item_id');
            $table->string('variant_key');
            $table->string('variant_name')->nullable();
            $table->string('variant_type')->nullable();
            $table->string('mutation')->nullable();
            $table->string('mutation_name')->nullable();
            $table->string('trait')->nullable();
            $table->string('trait_name')->nullable();
            $table->decimal('multiplier', 10, 4)->nullable();
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
    }
}
