<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemAlias;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemTranslation;
use App\Models\SeoItemVariant;
use App\Models\SeoNewsArticle;
use App\Models\SeoSite;
use App\Models\SeoUser;
use App\Models\SeoValueSource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabSiteContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class SeoAdminConsoleTest extends TestCase
{
    use CreatesSabWikiTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sab.hosts.www' => 'www.sabex.lab',
            'sab.hosts.admin' => 'x.sabex.lab',
            'sab.admin_allow_ips' => ['127.0.0.1', '::1'],
        ]);
        $this->createSeoTables();
        SeoSite::query()->create([
            'slug' => SabRenderService::SITE_SLUG,
            'name' => 'SAB',
            'domain' => 'sabexistcount.com',
            'base_url' => 'https://sabexistcount.com',
            'output_path' => '',
            'settings_json' => [
                'keep' => 'yes',
                'site_mode' => SabSiteContext::SITE_MODE_FULL,
            ],
        ]);
        SeoGame::query()->create([
            'seo_site_id' => 1,
            'slug' => 'steal-a-brainrot',
            'name' => 'Steal a Brainrot',
        ]);
    }

    public function test_admin_can_filter_and_patch_items(): void
    {
        $admin = SeoUser::factory()->create();
        $common = $this->makeItem('noobini', 'Noobini', 'Common', [
            'is_listed' => true,
            'is_hot' => false,
            'total_exists' => 1234,
            'wiki_page_url' => 'https://wiki.example/noobini',
            'wiki_page_status' => 'found',
            'supreme_value' => '12',
            'attributes_json' => [
                'rot_rocks' => [
                    'base_income' => 10,
                    'base_cost' => 5,
                    'base_price' => 8,
                ],
            ],
        ]);
        $secret = $this->makeItem('secret-rot', 'Secret Rot', 'Secret', [
            'is_listed' => false,
            'is_publish_html' => false,
        ]);
        $variant = SeoItemVariant::query()->create([
            'seo_item_id' => $common->id,
            'variant_key' => 'normal',
            'variant_name' => 'Normal',
        ]);
        SeoItemCurrentValue::query()->create([
            'seo_item_variant_id' => $variant->id,
            'seo_value_source_id' => 7,
            'value_normalized' => 88.5,
            'exist_count_normalized' => 1234,
        ]);

        $list = $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items')
            ->assertOk()
            ->assertJsonPath('counts.all', 2)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.slug', 'noobini')
            ->assertJsonPath('items.0.base_income', 10)
            ->assertJsonPath('items.0.base_price', 8)
            ->assertJsonPath('items.0.primary_value', 88.5)
            ->assertJsonPath('items.0.sources_count', 1)
            ->assertJsonPath('items.0.variants_count', 1)
            ->assertJsonPath('items.0.wiki_page_status', 'found')
            ->assertJsonPath('items.0.preview_path', 'http://www.sabex.lab/products/noobini')
            ->assertJsonPath('items.0.live_path', 'https://sabexistcount.com/products/noobini');
        $tags = collect($list->json('counts.tags'));
        $this->assertSame(1, $tags->firstWhere('key', '')['count']);
        $this->assertSame(1, $tags->firstWhere('key', 'common')['count']);
        $this->assertSame(0, $tags->firstWhere('key', 'secret')['count']);

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?rarity=secret')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'secret-rot');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?rarity=Secret')
            ->assertOk()
            ->assertJsonPath('items.0.slug', 'secret-rot');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?listed=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'noobini');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?wiki_page_status=found')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'noobini');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?wiki=found')
            ->assertOk()
            ->assertJsonPath('items.0.slug', 'noobini');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?wiki_page_status=unknown')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'secret-rot');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?has_exist_count=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'noobini');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?has_value=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'noobini');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items?has_value=0')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'secret-rot');

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/items/'.$common->id, [
                'name' => 'Noobini Prime',
                'rarity' => 'Rare',
                'is_listed' => true,
                'is_hot' => true,
                'sort_order' => 9,
            ])
            ->assertOk()
            ->assertJsonPath('item.name', 'Noobini Prime')
            ->assertJsonPath('item.rarity', 'Rare')
            ->assertJsonPath('item.is_listed', true)
            ->assertJsonPath('item.is_hot', true)
            ->assertJsonPath('item.sort_order', 9)
            ->assertJsonPath('item.sources_count', 1)
            ->assertJsonPath('item.variants_count', 1)
            ->assertJsonPath('item.primary_value', 88.5);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/items/'.$common->id, [
                'is_hot' => 'yes',
            ])
            ->assertStatus(422);

        $this->actingAs($admin, 'admin')
            ->postJson('http://x.sabex.lab/api/items/bulk', [
                'ids' => [$secret->id],
                'is_listed' => true,
                'is_publish_html' => true,
            ])
            ->assertOk()
            ->assertJsonPath('updated', 1);
        $secret->refresh();
        $this->assertTrue((bool) $secret->is_listed);
        $this->assertTrue((bool) $secret->is_publish_html);
    }

    public function test_admin_can_show_and_edit_item_like_geoflow(): void
    {
        $admin = SeoUser::factory()->create();
        $item = $this->makeItem('noobini', 'Noobini', 'Common', [
            'description' => 'old',
            'summary' => 'old summary',
        ]);
        $kept = SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'normal',
            'variant_name' => 'Normal',
        ]);
        $spare = SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'spare',
            'variant_name' => 'Spare',
        ]);
        SeoItemCurrentValue::query()->create([
            'seo_item_variant_id' => $kept->id,
            'seo_value_source_id' => 1,
            'value_normalized' => 10,
        ]);
        SeoItemTranslation::query()->create([
            'seo_item_id' => $item->id,
            'locale' => 'en',
            'name' => 'Noobini EN',
        ]);
        $source = SeoValueSource::query()->create([
            'slug' => 'rot-rocks',
            'name' => 'rot.rocks',
            'url' => 'https://rot.rocks',
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('item.slug', 'noobini')
            ->assertJsonPath('item.game.name', 'Steal a Brainrot')
            ->assertJsonPath('item.translations.0.locale', 'en')
            ->assertJsonPath('locales.0', 'en');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/value-sources')
            ->assertOk()
            ->assertJsonPath('sources.0.slug', 'rot-rocks');

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/items/'.$item->id, [
                'slug' => 'noobini-prime',
                'name' => 'Noobini Prime',
                'description' => 'updated',
                'summary' => 'new summary',
                'wiki_page_status' => 'found',
                'wiki_page_title' => 'Noobini',
            ])
            ->assertOk()
            ->assertJsonPath('item.slug', 'noobini-prime')
            ->assertJsonPath('item.description', 'updated')
            ->assertJsonPath('item.wiki_page_status', 'found')
            ->assertJsonPath('item.wiki_page_title', 'Noobini');
        $item->refresh();
        $this->assertSame('noobini-prime', $item->slug);

        $this->actingAs($admin, 'admin')
            ->putJson('http://x.sabex.lab/api/items/'.$item->id.'/translations', [
                'items' => [
                    ['locale' => 'en', 'name' => 'Noobini EN2', 'description' => '', 'seo_title' => '', 'seo_description' => ''],
                    ['locale' => 'es', 'name' => 'Noobini ES', 'description' => '', 'seo_title' => '', 'seo_description' => ''],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('item.translations.1.locale', 'es');
        $this->assertSame(2, SeoItemTranslation::query()->where('seo_item_id', $item->id)->count());

        $this->actingAs($admin, 'admin')
            ->putJson('http://x.sabex.lab/api/items/'.$item->id.'/translations', [
                'items' => [
                    ['locale' => 'es', 'name' => 'Noobini ES', 'description' => '', 'seo_title' => '', 'seo_description' => ''],
                ],
            ])
            ->assertOk();
        $this->assertSame(['es'], SeoItemTranslation::query()->where('seo_item_id', $item->id)->pluck('locale')->all());

        $this->actingAs($admin, 'admin')
            ->putJson('http://x.sabex.lab/api/items/'.$item->id.'/aliases', [
                'items' => [
                    ['seo_value_source_id' => $source->id, 'alias_name' => 'Noob'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('item.aliases.0.alias_name', 'Noob');
        $this->assertSame(1, SeoItemAlias::query()->where('seo_item_id', $item->id)->count());

        $this->actingAs($admin, 'admin')
            ->putJson('http://x.sabex.lab/api/items/'.$item->id.'/variants', [
                'items' => [
                    [
                        'id' => $spare->id,
                        'variant_key' => 'spare',
                        'variant_name' => 'Spare Only',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', '已有采集数据的规格不能直接删除');
        $this->assertTrue(SeoItemVariant::query()->whereKey($kept->id)->exists());
    }

    public function test_admin_can_list_and_patch_sites_without_changing_slug(): void
    {
        $admin = SeoUser::factory()->create();
        $site = SeoSite::query()->first();

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/sites')
            ->assertOk()
            ->assertJsonPath('sites.0.slug', SabRenderService::SITE_SLUG)
            ->assertJsonPath('sites.0.games_count', 1)
            ->assertJsonPath('sites.0.site_mode', 'full')
            ->assertJsonPath('sites.0.is_calculator_only', false)
            ->assertJsonPath('sites.0.preview_url', 'https://sabexistcount.com');

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/sites/'.$site->id, [
                'name' => 'SAB Lab',
                'domain' => 'sabex.lab',
                'slug' => 'hijack',
                'settings' => [
                    'site_mode' => SabSiteContext::SITE_MODE_CALCULATOR_ONLY,
                    'data_site_slug' => 'sab-exist-count',
                    'local_preview_base_url' => 'http://www.sabex.lab',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('site.name', 'SAB Lab')
            ->assertJsonPath('site.domain', 'sabex.lab')
            ->assertJsonPath('site.slug', SabRenderService::SITE_SLUG)
            ->assertJsonPath('site.site_mode', 'calculator_only')
            ->assertJsonPath('site.data_site_slug', 'sab-exist-count')
            ->assertJsonPath('site.local_preview_base_url', 'http://www.sabex.lab')
            ->assertJsonPath('site.preview_url', 'http://www.sabex.lab')
            ->assertJsonPath('site.is_calculator_only', true);

        $site->refresh();
        $this->assertSame(SabRenderService::SITE_SLUG, $site->slug);
        $this->assertSame('yes', data_get($site->settings_json, 'keep'));
        $this->assertSame('calculator_only', data_get($site->settings_json, 'site_mode'));
        $this->assertSame('http://www.sabex.lab', data_get($site->settings_json, 'local_preview_base_url'));
    }

    public function test_admin_can_filter_create_and_patch_news(): void
    {
        $admin = SeoUser::factory()->create();
        $news = $this->makeNews('saturday-update', 'Saturday Update', [
            'locale' => 'en',
            'status' => 'published',
            'sort_order' => 20,
            'cover_image_url' => '/uploads/images/sab/news/cover.png',
        ]);
        $this->makeNews('nota-pt', 'Nota PT', [
            'locale' => 'pt',
            'status' => 'draft',
            'sort_order' => 5,
        ]);
        $this->makeNews('about-us', 'About us', [
            'type' => SeoNewsArticle::TYPE_STATIC_PAGE,
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/news?type=200&locale=en')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('articles.0.slug', 'saturday-update')
            ->assertJsonPath('articles.0.cover_image_url', '/uploads/images/sab/news/cover.png')
            ->assertJsonPath('articles.0.preview_path', 'http://www.sabex.lab/news/saturday-update')
            ->assertJsonPath('articles.0.live_path', 'https://sabexistcount.com/news/saturday-update')
            ->assertJsonPath('locales.0', 'en');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/news?type=100&seo_site_id=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('articles.0.preview_path', 'http://www.sabex.lab/about-us');

        $created = $this->actingAs($admin, 'admin')
            ->postJson('http://x.sabex.lab/api/news', [
                'seo_site_id' => 1,
                'type' => SeoNewsArticle::TYPE_NEWS,
                'locale' => 'en',
                'title' => 'Fresh Drop',
                'excerpt' => 'Short',
                'cover_image_url' => '/uploads/images/sab/news/fresh.png',
                'body_html' => '<p>Hi</p><script>alert(1)</script>',
                'status' => 'draft',
                'sort_order' => 12,
                'is_system_log' => true,
                'meta_description' => 'Fresh meta',
            ])
            ->assertCreated()
            ->assertJsonPath('article.slug', 'fresh-drop')
            ->assertJsonPath('article.is_system_log', true)
            ->assertJsonPath('article.sort_order', 12)
            ->assertJsonPath('article.cover_image_url', '/uploads/images/sab/news/fresh.png')
            ->json('article');

        $this->assertStringContainsString('<p>Hi</p>', (string) $created['body_html']);
        $this->assertStringNotContainsString('script', strtolower((string) $created['body_html']));

        $system = $this->actingAs($admin, 'admin')
            ->postJson('http://x.sabex.lab/api/news', [
                'seo_site_id' => 1,
                'type' => SeoNewsArticle::TYPE_NEWS,
                'locale' => 'en',
                'title' => 'System Log Keep Layout',
                'body_html' => '<section data-sab-update-log-date="2026-09-04"><h2>Keep</h2><script>alert(1)</script></section>',
                'status' => 'draft',
                'is_system_log' => true,
            ])
            ->assertCreated()
            ->json('article');
        $this->assertStringContainsString('<section data-sab-update-log-date="2026-09-04">', (string) $system['body_html']);
        $this->assertStringContainsString('<h2>Keep</h2>', (string) $system['body_html']);
        $this->assertStringNotContainsString('script', strtolower((string) $system['body_html']));
        $this->assertNotEmpty($created['published_at']);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/news/'.$news->id, [
                'seo_site_id' => 1,
                'type' => SeoNewsArticle::TYPE_NEWS,
                'locale' => 'en',
                'title' => 'Saturday Update 2',
                'excerpt' => 'Updated',
                'cover_image_url' => '/uploads/images/sab/news/cover-2.png',
                'body_html' => '<p>Updated body</p>',
                'status' => 'draft',
                'published_at' => '2026-01-02 03:04:05',
                'sort_order' => 30,
                'is_system_log' => false,
                'meta_description' => 'Meta 2',
            ])
            ->assertOk()
            ->assertJsonPath('article.slug', 'saturday-update')
            ->assertJsonPath('article.title', 'Saturday Update 2')
            ->assertJsonPath('article.status', 'draft')
            ->assertJsonPath('article.sort_order', 30)
            ->assertJsonPath('article.cover_image_url', '/uploads/images/sab/news/cover-2.png');

        $this->actingAs($admin, 'admin')
            ->postJson('http://x.sabex.lab/api/news', [
                'seo_site_id' => 1,
                'type' => SeoNewsArticle::TYPE_NEWS,
                'locale' => 'en',
                'title' => 'Saturday Update',
                'status' => 'draft',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', '同一站点和语言下内容 slug 已存在');

        $this->actingAs($admin, 'admin')
            ->postJson('http://x.sabex.lab/api/news', [
                'seo_site_id' => 1,
                'type' => SeoNewsArticle::TYPE_STATIC_PAGE,
                'locale' => 'pt',
                'title' => 'Sobre',
                'status' => 'draft',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', '单页只支持 en 根目录版本');

        $this->actingAs($admin, 'admin')
            ->deleteJson('http://x.sabex.lab/api/news/'.$news->id)
            ->assertStatus(405);
    }

    public function test_admin_can_upload_news_image(): void
    {
        $admin = SeoUser::factory()->create();
        $file = UploadedFile::fake()->image('cover.jpg', 20, 20);

        $response = $this->actingAs($admin, 'admin')
            ->post('http://x.sabex.lab/api/news/images', [
                'file' => $file,
                'seo_site_id' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonStructure(['url', 'filename']);

        $filename = (string) $response->json('filename');
        $path = public_path('uploads/images/sab/news/'.$filename);
        $this->assertFileExists($path);
        $this->assertSame('/uploads/images/sab/news/'.$filename, $response->json('url'));
        File::delete($path);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeNews(string $slug, string $title, array $overrides = []): SeoNewsArticle
    {
        return SeoNewsArticle::query()->create(array_merge([
            'seo_site_id' => 1,
            'type' => SeoNewsArticle::TYPE_NEWS,
            'slug' => $slug,
            'locale' => 'en',
            'title' => $title,
            'excerpt' => '',
            'cover_image_url' => '',
            'body_html' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'meta_title' => '',
            'meta_description' => '',
            'sort_order' => 10,
            'is_system_log' => false,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeItem(string $slug, string $name, string $rarity, array $overrides = []): SeoItem
    {
        return SeoItem::query()->create(array_merge([
            'seo_game_id' => 1,
            'slug' => $slug,
            'name' => $name,
            'rarity' => $rarity,
            'description' => '',
            'summary' => '',
            'is_publish_html' => true,
            'is_listed' => true,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => [],
            'sort_order' => 0,
        ], $overrides));
    }
}
