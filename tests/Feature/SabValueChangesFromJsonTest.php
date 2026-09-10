<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemVariant;
use App\Models\SeoSite;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabValueChangesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class SabValueChangesFromJsonTest extends TestCase
{
    use CreatesSabWikiTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeoTables();
        File::delete(storage_path('app/seo/sab-price-history/json-gainer.json'));
        File::delete(storage_path('app/seo/sab-price-history/json-unchanged.json'));
        File::delete(storage_path('app/seo/sab-price-history/json-single.json'));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/seo/sab-price-history/json-gainer.json'));
        File::delete(storage_path('app/seo/sab-price-history/json-unchanged.json'));
        File::delete(storage_path('app/seo/sab-price-history/json-single.json'));
        parent::tearDown();
    }

    public function test_top_movers_use_today_versus_previous_json_point(): void
    {
        $this->travelTo(Carbon::parse('2026-09-09 12:00:00'));
        [$gainerBase] = $this->seedListedItem('json-gainer', 'Json Gainer');
        [$unchangedBase] = $this->seedListedItem('json-unchanged', 'Json Unchanged');
        [$singleBase] = $this->seedListedItem('json-single', 'Json Single');

        $this->writeHistory('json-gainer', $gainerBase->id, [
            ['date' => '2026-09-08', 'value' => 100],
            ['date' => '2026-09-09', 'value' => 120],
        ]);
        $this->writeHistory('json-unchanged', $unchangedBase->id, [
            ['date' => '2026-09-08', 'value' => 50],
            ['date' => '2026-09-09', 'value' => 50],
        ]);
        $this->writeHistory('json-single', $singleBase->id, [
            ['date' => '2026-09-09', 'value' => 80],
        ]);

        [$gainers, $losers] = app(SabValueChangesService::class)->topMoverSummaries(1, 5);

        $this->assertCount(1, $gainers);
        $this->assertSame('json-gainer', $gainers[0]['itemSlug']);
        $this->assertSame(20.0, $gainers[0]['delta']);
        $this->assertSame('+20%', $gainers[0]['deltaPctLabel']);
        $this->assertSame([], $losers);
    }

    /**
     * @return array{0: SeoItemVariant}
     */
    private function seedListedItem(string $slug, string $name): array
    {
        $site = SeoSite::query()->firstOrCreate(
            ['slug' => SabRenderService::SITE_SLUG],
            ['name' => 'SAB']
        );
        $game = SeoGame::query()->firstOrCreate(
            ['seo_site_id' => $site->id, 'slug' => 'steal-a-brainrot'],
            ['name' => 'Steal a Brainrot']
        );
        $item = SeoItem::query()->create([
            'seo_game_id' => $game->id,
            'slug' => $slug,
            'name' => $name,
            'is_listed' => true,
            'is_publish_html' => true,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => [],
        ]);
        $base = SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'base',
            'variant_name' => 'Base',
            'variant_type' => 'base',
            'multiplier' => 1,
        ]);

        return [$base];
    }

    /**
     * @param  list<array{date: string, value: float|int}>  $points
     */
    private function writeHistory(string $slug, int $variantId, array $points): void
    {
        $path = storage_path('app/seo/sab-price-history/'.$slug.'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'slug' => $slug,
            'variants' => [
                (string) $variantId => $points,
            ],
        ], JSON_UNESCAPED_SLASHES));
    }
}
