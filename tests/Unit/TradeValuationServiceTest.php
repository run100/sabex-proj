<?php

namespace Tests\Unit;

use App\Models\SeoItem;
use App\Services\Trades\BrainrotCatalogService;
use App\Services\Trades\TradeValuationService;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class TradeValuationServiceTest extends TestCase
{
    use CreatesSabWikiTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeoTables();
        config(['sab-trades.fair_threshold_percent' => 5]);
    }

    public function test_wfl_uses_configured_threshold_and_server_values(): void
    {
        $this->seedItem('noobini', 'Noobini', 100);
        $this->seedItem('elephant', 'Elephant', 200);

        $snapshot = app(TradeValuationService::class)->snapshot(
            [['slug' => 'noobini']],
            [['slug' => 'elephant']],
        );

        $this->assertSame(100.0, $snapshot['offering_total']);
        $this->assertSame(200.0, $snapshot['looking_total']);
        $this->assertSame('win', $snapshot['wfl']);
        $this->assertEqualsWithDelta(100.0, $snapshot['difference_percent'], 0.01);
    }

    public function test_fair_band_and_client_value_is_ignored(): void
    {
        $this->seedItem('a', 'A', 100);
        $this->seedItem('b', 'B', 103);

        $snapshot = app(TradeValuationService::class)->snapshot(
            [['slug' => 'a', 'value' => 999999]],
            [['slug' => 'b', 'value' => 1]],
        );

        $this->assertSame('fair', $snapshot['wfl']);
        $this->assertSame(100.0, $snapshot['offering_total']);
        $this->assertSame(103.0, $snapshot['looking_total']);
    }

    public function test_catalog_search_returns_seeded_item(): void
    {
        $this->seedItem('headless-horseman', 'Headless Horseman', 300000);
        $items = app(BrainrotCatalogService::class)->searchBrainrots('headless');
        $this->assertNotSame([], $items);
        $this->assertSame('headless-horseman', $items[0]['slug']);
        $this->assertSame(300000.0, $items[0]['current_value']);
    }

    private function seedItem(string $slug, string $name, float $value): void
    {
        SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => $slug,
            'name' => $name,
            'rarity' => 'Common',
            'description' => '',
            'summary' => '',
            'is_listed' => true,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '10',
            'total_exists' => 12,
            'attributes_json' => [
                'rot_rocks' => [
                    'name' => $name,
                    'base_income' => 10,
                    'robux_value' => $value,
                    'demand' => 'HIGH',
                ],
            ],
            'sort_order' => 0,
        ]);
    }
}
