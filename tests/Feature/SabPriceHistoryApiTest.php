<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemVariant;
use App\Models\SeoSite;
use App\Models\SeoValueSource;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabRotCalculatorSyncService;
use Illuminate\Support\Facades\File;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class SabPriceHistoryApiTest extends TestCase
{
    use CreatesSabWikiTables;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sab.hosts.www' => 'www.sabex.lab']);
        $this->createSeoTables();
        File::delete(storage_path('app/seo/sab-price-history/garama-and-madundung.json'));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/seo/sab-price-history/garama-and-madundung.json'));
        parent::tearDown();
    }

    public function test_price_history_api_returns_mutations_from_json(): void
    {
        $base = $this->seedGaramaWithCurrentValue();
        File::ensureDirectoryExists(storage_path('app/seo/sab-price-history'));
        File::put(storage_path('app/seo/sab-price-history/garama-and-madundung.json'), json_encode([
            'slug' => 'garama-and-madundung',
            'variants' => [
                (string) $base->id => [
                    ['date' => now()->subDays(2)->toDateString(), 'value' => 135],
                    ['date' => now()->subDay()->toDateString(), 'value' => 139],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES));

        $response = $this->get('http://www.sabex.lab/products/garama-and-madundung/price-history.json');
        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=300, public');
        $response->assertJsonPath('slug', 'garama-and-madundung');
        $response->assertJsonPath('mutations.0.id', 'base');
        $this->assertCount(2, $response->json('mutations.0.priceHistory'));
        $this->assertSame(139.0, (float) $response->json('mutations.0.robuxValue'));
    }

    public function test_price_history_api_returns_empty_mutations_when_json_missing(): void
    {
        $this->seedGaramaWithCurrentValue();

        $this->get('http://www.sabex.lab/products/garama-and-madundung/price-history.json')
            ->assertOk()
            ->assertJsonPath('slug', 'garama-and-madundung')
            ->assertJsonPath('mutations.0.priceHistory', []);
    }

    public function test_unknown_slug_returns_404(): void
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

        $this->get('http://www.sabex.lab/products/missing-brainrot/price-history.json')
            ->assertNotFound();
    }

    public function test_product_page_exposes_price_history_fetch_url(): void
    {
        $this->seedGaramaWithCurrentValue();

        $this->get('http://www.sabex.lab/products/garama-and-madundung')
            ->assertOk()
            ->assertSee('data-price-history-url="/products/garama-and-madundung/price-history.json"', false)
            ->assertDontSee('data-price-history=\'', false);
    }

    private function seedGaramaWithCurrentValue(): SeoItemVariant
    {
        SeoSite::query()->create([
            'slug' => SabRenderService::SITE_SLUG,
            'name' => 'SAB',
            'base_url' => 'https://sabexistcount.com',
        ]);
        SeoGame::query()->create([
            'seo_site_id' => 1,
            'slug' => 'steal-a-brainrot',
            'name' => 'Steal a Brainrot',
        ]);
        $source = SeoValueSource::query()->create([
            'slug' => SabRotCalculatorSyncService::SOURCE_SLUG,
            'name' => 'Rot Rocks Calculator',
            'enabled' => true,
        ]);
        $item = SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'garama-and-madundung',
            'name' => 'Garama and Madundung',
            'is_listed' => true,
            'is_publish_html' => true,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => ['rot_rocks' => ['rot_id' => 'brainrot-garama']],
        ]);
        $base = SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'base',
            'variant_name' => 'Base',
            'variant_type' => 'base',
            'multiplier' => 1,
        ]);
        SeoItemCurrentValue::query()->create([
            'seo_item_variant_id' => $base->id,
            'seo_value_source_id' => $source->id,
            'collected_at' => now(),
            'changed_at' => now(),
            'exist_count_raw' => '',
            'value_raw' => '139',
            'value_normalized' => 139,
            'currency' => 'ROBUX',
        ]);

        return $base;
    }
}
