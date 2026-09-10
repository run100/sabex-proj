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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class SabPriceHistoryBackfillTest extends TestCase
{
    use CreatesSabWikiTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeoTables();
        File::delete(storage_path('app/seo/sab-price-history/garama-and-madundung.json'));
        File::delete(storage_path('app/seo/sab-price-history/strawberry-elephant.json'));
        File::delete(storage_path('app/seo/sab-price-history/extra-backfill-item.json'));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/seo/sab-price-history/garama-and-madundung.json'));
        File::delete(storage_path('app/seo/sab-price-history/strawberry-elephant.json'));
        File::delete(storage_path('app/seo/sab-price-history/extra-backfill-item.json'));
        parent::tearDown();
    }

    public function test_backfill_writes_json_for_garama_and_updates_current_value(): void
    {
        [$base, $source] = $this->seedGarama();

        Http::fake([
            'https://rot.rocks/api/brainrots' => Http::response([
                'brainrots' => [[
                    'id' => 'brainrot-garama',
                    'name' => 'Garama and Madundung',
                    'slug' => 'garama-and-madundung',
                ]],
            ]),
            'https://rot.rocks/api/brainrots/brainrot-garama/price-history' => Http::response([
                'history' => [
                    ['date' => '2026-06-08', 'value' => 135],
                    ['date' => '2026-06-09', 'value' => 139],
                ],
                'latestPrice' => 139,
                'demand' => 'TERRIBLE',
            ]),
        ]);

        $this->artisan('seo:sab-price-history-backfill', ['--slug' => ['garama-and-madundung']])
            ->expectsOutputToContain('[ok] garama-and-madundung: 2 points')
            ->assertSuccessful();

        $path = storage_path('app/seo/sab-price-history/garama-and-madundung.json');
        $this->assertTrue(File::isFile($path));
        $payload = json_decode((string) File::get($path), true);
        $this->assertSame(135, $payload['variants'][(string) $base->id][0]['value']);
        $this->assertSame(139, $payload['variants'][(string) $base->id][1]['value']);
        $this->assertSame(0, SeoItemObservation::query()->count());
        $this->assertSame(139.0, (float) SeoItemCurrentValue::query()
            ->where('seo_item_variant_id', $base->id)
            ->where('seo_value_source_id', $source->id)
            ->value('value_normalized'));
    }

    public function test_resolve_backfill_slugs_returns_items_with_rot_id(): void
    {
        $this->seedSiteAndGame();
        SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'garama-and-madundung',
            'name' => 'Garama and Madundung',
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => ['rot_rocks' => ['rot_id' => 'brainrot-garama']],
        ]);
        SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'no-rot-id-item',
            'name' => 'No Rot Id Item',
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => ['rot_rocks' => ['name' => 'No Rot Id Item']],
        ]);
        SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'orphan-local-item',
            'name' => 'Orphan Local Item',
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => ['rot_rocks' => ['rot_id' => 'brainrot-orphan']],
        ]);

        Http::fake([
            'https://rot.rocks/api/brainrots' => Http::response([
                'brainrots' => [[
                    'id' => 'brainrot-garama',
                    'name' => 'Garama and Madundung',
                    'slug' => 'garama-and-madundung',
                ]],
            ]),
        ]);

        $this->assertSame(
            ['garama-and-madundung'],
            app(SabRotCalculatorSyncService::class)->resolveBackfillSlugs()
        );
    }

    public function test_command_without_slug_backfills_all_eligible_items(): void
    {
        [$garama] = $this->seedGarama();
        $extra = $this->seedItemWithBaseVariant('extra-backfill-item', 'Extra Backfill Item', 'brainrot-extra');

        Http::fake([
            'https://rot.rocks/api/brainrots' => Http::response([
                'brainrots' => [
                    [
                        'id' => 'brainrot-garama',
                        'name' => 'Garama and Madundung',
                        'slug' => 'garama-and-madundung',
                    ],
                    [
                        'id' => 'brainrot-extra',
                        'name' => 'Extra Backfill Item',
                        'slug' => 'extra-backfill-item',
                    ],
                ],
            ]),
            'https://rot.rocks/api/brainrots/brainrot-garama/price-history' => Http::response([
                'history' => [
                    ['date' => '2026-06-08', 'value' => 135],
                    ['date' => '2026-06-09', 'value' => 139],
                ],
                'latestPrice' => 139,
            ]),
            'https://rot.rocks/api/brainrots/brainrot-extra/price-history' => Http::response([
                'history' => [
                    ['date' => '2026-06-08', 'value' => 10],
                    ['date' => '2026-06-09', 'value' => 12],
                ],
                'latestPrice' => 12,
            ]),
        ]);

        $this->artisan('seo:sab-price-history-backfill')
            ->expectsOutputToContain('Starting backfill (2 slugs)...')
            ->expectsOutputToContain('[ok] extra-backfill-item: 2 points')
            ->expectsOutputToContain('[ok] garama-and-madundung: 2 points')
            ->expectsOutputToContain('Written=2 skipped=0')
            ->assertSuccessful();

        $this->assertTrue(File::isFile(storage_path('app/seo/sab-price-history/garama-and-madundung.json')));
        $this->assertTrue(File::isFile(storage_path('app/seo/sab-price-history/extra-backfill-item.json')));
        $this->assertFalse(File::isFile(storage_path('app/seo/sab-price-history/strawberry-elephant.json')));

        $extraPayload = json_decode((string) File::get(storage_path('app/seo/sab-price-history/extra-backfill-item.json')), true);
        $this->assertSame(12, $extraPayload['variants'][(string) $extra->id][1]['value']);
        $this->assertSame(0, SeoItemObservation::query()->count());
        $this->assertSame(139.0, (float) SeoItemCurrentValue::query()
            ->where('seo_item_variant_id', $garama->id)
            ->value('value_normalized'));
    }

    /**
     * @return array{0: SeoItemVariant, 1: SeoValueSource}
     */
    private function seedGarama(): array
    {
        $this->seedSiteAndGame();
        $source = SeoValueSource::query()->create([
            'slug' => SabRotCalculatorSyncService::SOURCE_SLUG,
            'name' => 'Rot Rocks Calculator',
            'url' => 'https://rot.rocks/trading/calculator',
            'parser_type' => 'json_api',
            'priority' => 80,
            'is_primary_source' => false,
            'enabled' => true,
        ]);
        $base = $this->seedItemWithBaseVariant('garama-and-madundung', 'Garama and Madundung', 'brainrot-garama');

        return [$base, $source];
    }

    private function seedItemWithBaseVariant(string $slug, string $name, string $rotId): SeoItemVariant
    {
        $item = SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => $slug,
            'name' => $name,
            'is_listed' => true,
            'is_publish_html' => true,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => ['rot_rocks' => ['rot_id' => $rotId]],
        ]);

        return SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'base',
            'variant_name' => 'Base',
            'variant_type' => 'base',
            'multiplier' => 1,
        ]);
    }

    private function seedSiteAndGame(): void
    {
        SeoSite::query()->create([
            'slug' => SabRenderService::SITE_SLUG,
            'name' => 'SAB',
        ]);
        SeoGame::query()->create([
            'seo_site_id' => 1,
            'slug' => 'steal-a-brainrot',
            'name' => 'Steal a Brainrot',
        ]);
    }
}
