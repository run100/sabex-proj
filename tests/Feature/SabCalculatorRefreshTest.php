<?php

namespace Tests\Feature;

use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemObservation;
use App\Models\SeoItemVariant;
use App\Models\SeoValueSource;
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
    }

    public function test_refresh_updates_existing_listed_item_json_and_keeps_old_points(): void
    {
        $source = SeoValueSource::query()->create([
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

        Http::fake([
            'https://rot.rocks/api/brainrots' => Http::response([
                'brainrots' => [
                    [
                        'id' => 'brainrot-garama',
                        'name' => 'Refresh Test Brainrot',
                        'robuxValue' => 80,
                        'demand' => 'HIGH',
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
        ]);

        $this->artisan('seo:sab-calculator-refresh')
            ->expectsOutput('SAB calculator refresh completed.')
            ->assertSuccessful();

        $this->assertSame(1, SeoItem::query()->count());
        $this->assertSame(80.0, (float) SeoItemCurrentValue::query()->where('seo_item_variant_id', $base->id)->value('value_normalized'));
        $this->assertSame(1, SeoItemObservation::query()->where('seo_item_variant_id', $base->id)->count());

        $payload = json_decode((string) File::get($path), true);
        $this->assertSame('2026-01-01', $payload['variants'][(string) $base->id][0]['date']);
        $this->assertSame(10, $payload['variants'][(string) $base->id][0]['value']);
        $this->assertSame(now()->toDateString(), $payload['variants'][(string) $base->id][2]['date']);
        $this->assertSame(80, $payload['variants'][(string) $base->id][2]['value']);
        $this->assertSame(385, $payload['variants'][(string) $rainbow->id][0]['value']);
        $this->assertSame(340, $payload['variants'][(string) $rainbow->id][1]['value']);

        $this->artisan('seo:sab-calculator-refresh')->assertSuccessful();
        $this->assertSame(1, SeoItemObservation::query()->where('seo_item_variant_id', $base->id)->count());

        File::delete($path);
        unset($source);
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
        ] as $table) {
            Schema::dropIfExists($table);
        }

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
            $table->boolean('is_listed')->default(false);
            $table->timestamps();
        });
        Schema::create('seo_item_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_item_id');
            $table->string('variant_key');
            $table->string('variant_name')->nullable();
            $table->string('variant_type')->nullable();
            $table->string('mutation_name')->nullable();
            $table->decimal('multiplier', 10, 4)->nullable();
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
