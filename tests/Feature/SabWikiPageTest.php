<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemObservation;
use App\Models\SeoItemVariant;
use App\Models\SeoNewsArticle;
use App\Models\SeoSite;
use App\Models\SeoValueSource;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabRotCalculatorSyncService;
use App\Services\Seo\SabWikiCatalogService;
use App\Services\Seo\SabWikiPageDefinitions;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class SabWikiPageTest extends TestCase
{
    use CreatesSabWikiTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeoTables();
        File::delete(SabWikiCatalogService::path());
    }

    protected function tearDown(): void
    {
        File::delete(SabWikiCatalogService::path());
        parent::tearDown();
    }

    public function test_wiki_catalog_command_writes_snapshot_and_pages_reuse_it(): void
    {
        $game = $this->seedGame();
        $settingsBefore = $game->fresh()->settings_json;
        $source = $this->seedSource();
        $item = $this->seedItem($game, [
            'slug' => 'snapshot-brainrot',
            'name' => 'Snapshot Brainrot',
            'rarity' => 'Secret',
            'total_exists' => 12,
            'attributes_json' => [
                'rot_rocks' => [
                    'base_cost' => 1000,
                    'base_income' => 500,
                    'demand' => 'HIGH',
                    'trend' => 'rising',
                ],
            ],
        ]);
        $variant = $this->seedValue($item, $source, 250, '2026-08-30 08:30:00');
        $this->seedObservation($variant, $source, 100, now()->subDay());
        $this->seedObservation($variant, $source, 250, now());

        Http::preventStrayRequests();
        $this->artisan('seo:sab-wiki-catalog')
            ->expectsOutput('SAB Wiki catalog completed.')
            ->expectsOutput('Rows: 1')
            ->assertSuccessful();

        $payload = json_decode((string) File::get(SabWikiCatalogService::path()), true);
        $this->assertSame(1, $payload['schema_version'] ?? null);
        $this->assertNotEmpty($payload['generated_at'] ?? null);
        $this->assertCount(1, $payload['rows'] ?? []);
        $this->assertSame('snapshot-brainrot', $payload['rows'][0]['slug'] ?? null);
        $this->assertSame(1, data_get($payload, 'summary.total'));

        DB::flushQueryLog();
        DB::enableQueryLog();
        $context = app(SabRenderService::class)->wikiViewContext();
        $queries = DB::getQueryLog();
        $sql = implode("\n", array_map(fn (array $query): string => strtolower((string) ($query['query'] ?? '')), $queries));

        $this->assertSame(1, $context['wikiTotal']);
        $this->assertStringContainsString('Snapshot Brainrot', (string) ($context['wikiRows'][0]['name'] ?? ''));
        $this->assertStringNotContainsString('from `seo_items`', $sql);
        $this->assertStringNotContainsString('seo_item_variants', $sql);
        $this->assertStringNotContainsString('seo_item_current_values', $sql);

        $admin = app(SabRenderService::class)->wikiTopicViewContext(SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE);
        $this->assertSame(1, $admin['wikiTotal']);
        $this->assertSame($settingsBefore, $game->fresh()->settings_json);
        $this->assertSame(SabWikiCatalogService::publicUrl(), SabWikiCatalogService::read()['url'] ?? null);

        $dataResponse = $this->get(SabWikiCatalogService::publicUrl());
        $dataResponse->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString('max-age=3600', (string) $dataResponse->headers->get('Cache-Control'));
    }

    public function test_wiki_catalog_command_keeps_previous_file_when_empty(): void
    {
        $this->seedGame();
        $previous = json_encode([
            'schema_version' => 1,
            'generated_at' => '2026-09-01T00:00:00+00:00',
            'rows' => [['slug' => 'previous-brainrot']],
            'summary' => ['total' => 1],
        ], JSON_UNESCAPED_SLASHES);
        File::ensureDirectoryExists(dirname(SabWikiCatalogService::path()));
        File::put(SabWikiCatalogService::path(), $previous);

        Http::preventStrayRequests();
        $this->artisan('seo:sab-wiki-catalog')
            ->expectsOutput('SAB Wiki catalog is empty; previous file was kept.')
            ->assertFailed();

        $this->assertSame($previous, File::get(SabWikiCatalogService::path()));
    }

    public function test_wiki_uses_database_fields_and_keeps_unknown_values_explicit(): void
    {
        $game = $this->seedGame();
        $source = $this->seedSource();

        $common = $this->seedItem($game, [
            'slug' => 'database-common',
            'name' => 'Database Common',
            'rarity' => 'Common',
            'total_exists' => 123,
            'attributes_json' => [
                'rot_rocks' => [
                    'base_cost' => 1_000_000,
                    'base_income' => 5_000,
                    'demand' => 'HIGH',
                    'trend' => 'lowering',
                ],
            ],
        ]);
        $variant = $this->seedValue($common, $source, 25, '2026-08-29 12:00:00');
        $this->seedObservation($variant, $source, 63.1, now()->subDays(2));
        $this->seedObservation($variant, $source, 25, now()->subDay());

        $estimate = $this->seedItem($game, [
            'slug' => 'estimated-secret',
            'name' => 'Estimated Secret',
            'rarity' => 'Secret',
            'total_exists' => null,
            'exist_estimate_low' => 100,
            'exist_estimate_high' => 300,
            'exist_estimate_reason' => 'Test range',
            'exist_estimate_confidence' => 'low',
            'attributes_json' => [],
        ]);
        $this->seedItem($game, [
            'slug' => 'unknown-og',
            'name' => 'Unknown OG',
            'rarity' => 'OG',
            'total_exists' => null,
            'attributes_json' => [],
            'is_listed' => false,
        ]);

        $this->seedItem($game, [
            'slug' => 'private-item',
            'name' => 'Private Item',
            'rarity' => 'Secret',
            'attributes_json' => [],
            'is_publish_html' => false,
        ]);

        SeoItem::query()->update(['updated_at' => '2026-08-28 00:00:00']);
        SeoItem::query()->whereKey($estimate->id)->update(['updated_at' => '2026-08-30 08:30:00']);

        $context = app(SabRenderService::class)->wikiViewContext();
        $rows = collect($context['wikiRows'])->keyBy('slug');

        $this->assertSame(3, $context['wikiTotal']);
        $this->assertSame(['database-common', 'estimated-secret', 'unknown-og'], $rows->keys()->sort()->values()->all());
        $this->assertSame('$1M', $rows['database-common']['costLabel']);
        $this->assertSame('$5K/s', $rows['database-common']['incomeLabel']);
        $this->assertSame('123', $rows['database-common']['existCountLabel']);
        $this->assertSame('25', $rows['database-common']['tradeValueLabel']);
        $this->assertSame('High', $rows['database-common']['demandLabel']);
        $this->assertSame('Lowering', $rows['database-common']['trendLabel']);
        $this->assertSame('down', $rows['database-common']['changeDirection']);
        $this->assertSame('-60.4%', $rows['database-common']['deltaPctLabel']);
        $this->assertNull($rows['unknown-og']['demandLabel']);
        $this->assertNull($rows['unknown-og']['trendLabel']);
        $this->assertNull($rows['unknown-og']['deltaPctLabel']);
        $this->assertSame('100', $rows['estimated-secret']['existCountLabel']);
        $this->assertSame('Estimate', $rows['estimated-secret']['existCountBadge']);
        $this->assertSame('-', $rows['unknown-og']['costLabel']);
        $this->assertSame('-', $rows['unknown-og']['incomeLabel']);
        $this->assertSame('-', $rows['unknown-og']['existCountLabel']);
        $this->assertSame('-', $rows['unknown-og']['tradeValueLabel']);
        $this->assertNull($rows['database-common']['obtainMethod']);
        $this->assertNull($rows['unknown-og']['obtainMethod']);
        $this->assertStringStartsWith('2026-08-30T08:30:00', (string) $context['wikiUpdatedAt']);
        $this->assertCount(8, $context['wikiFaqItems']);
        foreach ($context['wikiFaqItems'] as $faq) {
            $this->assertSame(
                $faq['answer'],
                html_entity_decode(strip_tags((string) $faq['answer_html']), ENT_QUOTES | ENT_HTML5),
            );
        }

        $response = $this->get('/wiki');

        $response->assertOk();
        $response->assertSee('Steal a Brainrot Wiki | SAB Values, Calculator &amp; Exist Count', false);
        $response->assertSee('Steal a Brainrot Wiki with all Brainrots, rarity lists, SAB Values, trade calculator, and live exist counts. Check cost, income, updates, and FAQs.', false);
        $response->assertSee('<h1 class="sab-wiki-title">Steal a Brainrot Wiki: SAB Values, Calculator &amp; Exist Count</h1>', false);
        $response->assertDontSee('sab-wiki-eyebrow', false);
        $response->assertSee('Use this Steal a Brainrot Wiki to browse every Brainrot rarity, then open SAB Values, the SAB Calculator, or SAB Exist Count tools to compare trade value, income, and supply across 3 published Brainrots.', false);
        $response->assertSee('3 published items · 8 rarity pages · Updated', false);
        $response->assertSee('SAB Values', false);
        $response->assertSee('Steal a Brainrot Wiki with SAB Values, Calculator, and Exist Count tools.', false);
        $response->assertSee('<meta name="robots" content="index,follow,max-image-preview:large"', false);
        $response->assertSee('<link rel="canonical" href="https://sabexistcount.com/wiki"', false);
        $response->assertSee('Database Common');
        $response->assertSee('Estimated Secret');
        $response->assertSee('Unknown OG');
        $response->assertDontSee('Private Item');
        $response->assertSee('href="/products/database-common"', false);
        $response->assertSee('Unknown', false);
        $response->assertDontSee('>$0<', false);
        $response->assertSee('CollectionPage', false);
        $response->assertSee('ItemList', false);
        $response->assertSee('BreadcrumbList', false);
        $response->assertSee('FAQPage', false);
        $response->assertSee('What is SAB Exist Count?', false);
        $response->assertSee('Open the Exist Count List', false);
        $response->assertSee('id="sab-tools"', false);
        $response->assertSee('SAB Values, Calculator &amp; Exist Count Tools', false);
        $response->assertSee('href="/sab-value-list"', false);
        $response->assertSee('href="/steal-a-brainrot-trading-calculator"', false);
        $response->assertSee('href="/sab-exist-count-list"', false);
        $response->assertDontSee('data-wiki-search', false);
        $response->assertDontSee('data-wiki-sort', false);
        $response->assertDontSee('data-wiki-rarity="secret"', false);
        $html = (string) $response->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertSame(8, substr_count($html, '<details>'));
        $schema = $this->wikiJsonLdFromHtml($html);
        $faqSchema = collect($schema['@graph'] ?? [])->firstWhere('@type', 'FAQPage');
        $this->assertCount(8, $faqSchema['mainEntity'] ?? []);
        $this->assertSame(
            collect($context['wikiFaqItems'])->pluck('question')->all(),
            collect($faqSchema['mainEntity'] ?? [])->pluck('name')->all(),
        );
        $this->assertSame(
            collect($context['wikiFaqItems'])->pluck('answer')->all(),
            collect($faqSchema['mainEntity'] ?? [])->map(fn (array $question): string => (string) ($question['acceptedAnswer']['text'] ?? ''))->all(),
        );
        $this->assertSame(0, substr_count($html, '<tr data-wiki-row'));
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="/wiki"'));
        $response->assertSee('href="/wiki/all-brainrots"', false);
        $response->assertSee('href="/wiki/steal-a-brainrot-rebirth-list"', false);
        $response->assertSee('>Rebirth List</strong>', false);
        $response->assertSee('<p class="sab-wiki-nav-title" id="wiki-hub-title">Browse this Wiki</p>', false);
        $response->assertSee('sab-wiki-drawer', false);
        $response->assertSee('data-wiki-drawer-open', false);
        $response->assertSee('>Menu</p>', false);
        $response->assertSee('>Tools</p>', false);
        $response->assertSee('href="/steal-a-brainrot-trading-calculator"', false);
        $response->assertSee('href="/sab-value-list"', false);
        $response->assertSee('sab-wiki-drawer__icon', false);
        $response->assertSee('href="/wiki/all-common-brainrots"', false);
        $response->assertSee('href="/wiki/all-og-brainrots"', false);
        $response->assertSee('brainrot-rarity-common', false);
        $response->assertSee('sab-wiki-drawer__count', false);
        $this->assertStringContainsString(
            '<span>Common</span><span class="sab-wiki-drawer__count">1</span>',
            $html,
        );
        $this->assertStringContainsString(
            'href="/wiki" class="sab-wiki-drawer__link is-active"',
            $html,
        );
        $response->assertSee('<p class="sab-wiki-nav-title" id="wiki-contents-title">On this page</p>', false);
        $response->assertDontSee('<h2 id="wiki-hub-title">', false);
        $response->assertDontSee('<h2 id="wiki-contents-title">', false);
        $response->assertSee('id="newest"', false);
        $response->assertSee('id="brainrot-rarities"', false);
        $this->assertLessThan(
            strpos($html, 'id="newest"'),
            strpos($html, 'id="brainrot-rarities"'),
        );
        $response->assertSee('id="recent-updates"', false);
        $response->assertDontSee('id="lucky-blocks"', false);
        $response->assertDontSee('id="fusions"', false);
        $response->assertDontSee('id="rebirths"', false);
        $response->assertDontSee('id="rituals"', false);
        $response->assertDontSee('id="events"', false);
        $response->assertDontSee('/wiki/brainrots', false);
        $response->assertDontSee('Quick Facts', false);

        $catalog = $this->get('/wiki/all-brainrots');
        $catalog->assertOk();
        $catalog->assertSee('All Brainrots in Steal a Brainrot | Cost, Income &amp; Exist Count', false);
        $catalog->assertSee('<h1 class="sab-wiki-title">All Brainrots in Steal a Brainrot</h1>', false);
        $catalog->assertDontSee('$1M', false);
        $catalog->assertSee('$5K/s', false);
        $catalog->assertSee('>25<', false);
        $catalog->assertSee('sab-wiki-demand', false);
        $catalog->assertSee('High', false);
        $catalog->assertSee('sab-wiki-trend-icon is-down', false);
        $catalog->assertSee('-60.4%', false);
        $catalog->assertDontSee('$0.25', false);
        $catalog->assertSee('Trade Value', false);
        $catalog->assertSee('data-label="Demand"', false);
        $catalog->assertDontSee('data-label="Cost"', false);
        $catalog->assertDontSee('Cost: highest first', false);
        $catalog->assertSee('sab-wiki-name-meta', false);
        $catalog->assertDontSee('sab-wiki-name-value', false);
        $catalogHtml = (string) $catalog->getContent();
        $this->assertSame(3, substr_count($catalogHtml, '<tr data-wiki-row'));
        $this->assertStringContainsString('value="value-desc" selected', $catalogHtml);
        $this->assertStringContainsString('Value ↓', $catalogHtml);
        $this->assertStringContainsString('value="value-asc"', $catalogHtml);
        $this->assertStringContainsString('data-label="Trade Value"', $catalogHtml);
        $this->assertStringContainsString('class="sab-wiki-name" href="/products/database-common"', $catalogHtml);
        $this->assertLessThan(
            (int) strpos($catalogHtml, 'href="/products/estimated-secret"'),
            (int) strpos($catalogHtml, 'href="/products/database-common"'),
            'Valued catalog rows should render before rows without a trade value',
        );
        $catalog->assertSee('data-wiki-search', false);
        $catalog->assertDontSee('data-wiki-rarity="secret"', false);
        $catalog->assertSee('href="/wiki/all-secret-brainrots"', false);
    }

    public function test_wiki_catalog_and_topic_default_to_value_desc_within_rarity_groups(): void
    {
        $game = $this->seedGame();
        $source = $this->seedSource();

        $alphaCommon = $this->seedItem($game, [
            'slug' => 'alpha-common',
            'name' => 'Alpha Common',
            'rarity' => 'Common',
        ]);
        $zetaCommon = $this->seedItem($game, [
            'slug' => 'zeta-common',
            'name' => 'Zeta Common',
            'rarity' => 'Common',
        ]);
        $this->seedValue($zetaCommon, $source, 80, '2026-08-29 12:00:00');
        $this->seedValue($alphaCommon, $source, 20, '2026-08-29 12:00:00');
        $this->seedItem($game, [
            'slug' => 'no-value-common',
            'name' => 'Beta Common',
            'rarity' => 'Common',
        ]);

        $lucky = $this->seedItem($game, [
            'slug' => 'zeta-lucky',
            'name' => 'Zeta Lucky',
            'rarity' => 'Secret',
            'attributes_json' => [
                'manual_update' => ['obtain_method' => 'Lucky Block'],
            ],
        ]);
        $this->seedValue($lucky, $source, 300, '2026-08-29 12:00:00');
        $this->seedItem($game, [
            'slug' => 'alpha-lucky',
            'name' => 'Alpha Lucky',
            'rarity' => 'Secret',
            'attributes_json' => [
                'manual_update' => ['obtain_method' => 'Lucky Block'],
            ],
        ]);

        $context = app(SabRenderService::class)->wikiCatalogViewContext('wiki/all-brainrots');
        $commonGroup = collect($context['wikiGroups'])->firstWhere('key', 'common');
        $this->assertSame(
            ['zeta-common', 'alpha-common', 'no-value-common'],
            collect($commonGroup['rows'] ?? [])->pluck('slug')->all(),
        );

        $catalog = $this->get('/wiki/all-brainrots');
        $catalog->assertOk();
        $catalogHtml = (string) $catalog->getContent();
        $this->assertStringContainsString('value="value-desc" selected', $catalogHtml);
        $this->assertLessThan(
            (int) strpos($catalogHtml, 'href="/products/no-value-common"'),
            (int) strpos($catalogHtml, 'href="/products/zeta-common"'),
        );
        $this->assertLessThan(
            (int) strpos($catalogHtml, 'href="/products/alpha-common"'),
            (int) strpos($catalogHtml, 'href="/products/zeta-common"'),
        );

        $secret = $this->get('/wiki/all-secret-brainrots');
        $secret->assertOk();
        $secretHtml = (string) $secret->getContent();
        $this->assertStringContainsString('value="value-desc" selected', $secretHtml);
        $this->assertLessThan(
            (int) strpos($secretHtml, 'href="/products/alpha-lucky"'),
            (int) strpos($secretHtml, 'href="/products/zeta-lucky"'),
        );

        $topic = $this->get('/wiki/all-lucky-blocks');
        $topic->assertOk();
        $topicHtml = (string) $topic->getContent();
        $this->assertStringContainsString('value="value-desc" selected', $topicHtml);
        $this->assertLessThan(
            (int) strpos($topicHtml, 'href="/products/alpha-lucky"'),
            (int) strpos($topicHtml, 'href="/products/zeta-lucky"'),
        );
    }

    public function test_wiki_hub_shows_newest_topics_and_confirmed_obtain_only(): void
    {
        $game = $this->seedGame();
        $this->seedItem($game, [
            'slug' => 'bee-lucky-secret',
            'name' => 'Bee Lucky Secret',
            'rarity' => 'Secret',
            'total_exists' => 40,
            'attributes_json' => [
                'manual_update' => [
                    'obtain_method' => 'Bee Lucky Block',
                    'checked_at' => now()->toIso8601String(),
                ],
            ],
        ]);
        $this->seedItem($game, [
            'slug' => 'admin-lucky-block',
            'name' => 'Admin Lucky Block',
            'rarity' => 'Secret',
            'total_exists' => 80,
            'attributes_json' => [],
        ]);
        $this->seedItem($game, [
            'slug' => 'blank-obtain-common',
            'name' => 'Blank Obtain Common',
            'rarity' => 'Common',
            'attributes_json' => [
                'manual_update' => [
                    'obtain_method' => 'Unknown',
                ],
            ],
        ]);
        SeoNewsArticle::query()->create([
            'seo_site_id' => $game->seo_site_id,
            'type' => SeoNewsArticle::TYPE_NEWS,
            'slug' => 'wiki-recent-update',
            'locale' => 'en',
            'title' => 'Wiki Recent Update',
            'excerpt' => 'Tracker note.',
            'body_html' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'meta_title' => 'Wiki Recent Update',
            'meta_description' => 'Tracker note.',
        ]);

        $context = app(SabRenderService::class)->wikiViewContext();
        $rows = collect($context['wikiRows'])->keyBy('slug');
        $topics = collect($context['wikiTopics'])->keyBy('key');

        $this->assertSame('Bee Lucky Block', $rows['bee-lucky-secret']['obtainMethod']);
        $this->assertNull($rows['blank-obtain-common']['obtainMethod']);
        $this->assertTrue($rows['bee-lucky-secret']['isNew']);
        $this->assertContains('bee-lucky-secret', collect($context['wikiNewest'])->pluck('slug'));
        $this->assertContains('bee-lucky-secret', collect($topics['lucky-blocks']['rows'])->pluck('slug'));
        $this->assertContains('admin-lucky-block', collect($topics['lucky-blocks']['rows'])->pluck('slug'));
        $this->assertSame('Wiki Recent Update', $context['wikiNews'][0]['title'] ?? null);

        $response = $this->get('/wiki');
        $response->assertOk();
        $response->assertSee('Bee Lucky Block', false);
        $response->assertSee('Newest Brainrots', false);
        $response->assertDontSee('>Lucky Blocks</strong>', false);
        $response->assertDontSee('href="/wiki/all-lucky-blocks"', false);
        $response->assertDontSee('href="/all-lucky-blocks"', false);
        $response->assertDontSee('id="lucky-blocks"', false);
        $response->assertDontSee('<h2>Lucky Blocks</h2>', false);
        $response->assertSee('Wiki Recent Update', false);
        $response->assertSee('href="/news/wiki-recent-update"', false);
        $html = (string) $response->getContent();
        $this->assertStringNotContainsString('data-label="Obtain">Unknown', $html);
        $this->assertStringNotContainsString('/wiki/brainrots', $html);
        $this->assertStringNotContainsString('id="rebirths"', $html);
        $this->assertStringNotContainsString('<h2>Rebirths</h2>', $html);
        $this->assertStringNotContainsString('<h2>Rituals</h2>', $html);

        $lucky = $this->get('/wiki/all-lucky-blocks');
        $lucky->assertOk();
        $lucky->assertSee('<h1 class="sab-wiki-title">All Lucky Blocks</h1>', false);
        $lucky->assertSee('Bee Lucky Secret', false);
        $lucky->assertSee('Admin Lucky Block', false);
        $lucky->assertSee('Bee Lucky Block', false);

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('href="/wiki"', false);
        $home->assertDontSee('sab-home-wiki-cta', false);
        $home->assertDontSee('Browse all Brainrots by rarity, with cost, income, exist count, and trade value in one catalog.', false);
    }

    public function test_live_sitemap_lists_wiki_and_shipping_pages(): void
    {
        $game = $this->seedGame();
        $this->seedItem($game, [
            'slug' => 'rendered-brainrot',
            'name' => 'Rendered Brainrot',
            'rarity' => 'Rare',
            'total_exists' => 50,
            'attributes_json' => [
                'rot_rocks' => ['base_cost' => 2_000, 'base_income' => 15],
            ],
        ]);

        $wiki = $this->get('/wiki');
        $wiki->assertOk();
        $wikiHtml = (string) $wiki->getContent();
        $this->assertStringContainsString('Rendered Brainrot', $wikiHtml);
        $this->assertStringContainsString('href="/wiki/all-brainrots"', $wikiHtml);
        $this->assertStringContainsString('href="/wiki/steal-a-brainrot-rebirth-list"', $wikiHtml);
        $this->assertStringContainsString('>Rebirth List</strong>', $wikiHtml);
        $this->assertStringContainsString('Steal a Brainrot Wiki | SAB Values, Calculator &amp; Exist Count', $wikiHtml);
        $this->assertStringContainsString('<h1 class="sab-wiki-title">Steal a Brainrot Wiki: SAB Values, Calculator &amp; Exist Count</h1>', $wikiHtml);
        $this->assertStringContainsString('name="robots" content="index,follow,max-image-preview:large"', $wikiHtml);
        $this->assertStringNotContainsString('noindex', $wikiHtml);

        $catalog = $this->get('/wiki/all-brainrots');
        $catalog->assertOk();
        $this->assertStringContainsString('Rendered Brainrot', (string) $catalog->getContent());
        $this->assertStringContainsString('href="/products/rendered-brainrot"', (string) $catalog->getContent());

        $rebirth = $this->get('/wiki/steal-a-brainrot-rebirth-list');
        $rebirth->assertOk();
        $this->assertStringContainsString('id="all-rebirth-requirements"', (string) $rebirth->getContent());

        $admin = $this->get('/wiki/admin-abuse');
        $admin->assertOk();
        $this->assertStringContainsString('Steal a Brainrot Admin Abuse Time Today', (string) $admin->getContent());

        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $index = (string) $response->getContent();
        $origin = 'https://sabexistcount.com';
        $this->assertStringContainsString('<sitemapindex', $index);
        $this->assertStringContainsString($origin.'/sitemaps/main.xml', $index);
        $this->assertStringNotContainsString('trades.sabexistcount.com', $index);

        $main = $this->get('/sitemaps/main.xml');
        $main->assertOk();
        $sitemap = (string) $main->getContent();
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/wiki</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/sab-exist-count-list</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/pt/sab-exist-count-list</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/sab-value-list</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/steal-a-brainrot-trading-calculator</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/steal-a-brainrot-codes</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/games</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/exist-count-gallery</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/value-changes</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/news</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/products/rendered-brainrot</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/about-us</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/trading</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/trading/new</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/trading/pending</loc>'));
        $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/trading/completed</loc>'));
        foreach (SabWikiPageDefinitions::shippingPageSlugs() as $slug) {
            $this->assertSame(1, substr_count($sitemap, '<loc>'.$origin.'/'.$slug.'</loc>'));
        }
        foreach (SabWikiPageDefinitions::topicPageSlugs() as $slug) {
            if (in_array($slug, SabWikiPageDefinitions::shippingTopicPageSlugs(), true)) {
                continue;
            }
            $this->assertStringNotContainsString($origin.'/'.$slug.'</loc>', $sitemap);
        }
        $this->assertStringNotContainsString('/wiki.html</loc>', $sitemap);
        $this->assertStringNotContainsString('/wiki/brainrots', $sitemap);
        $this->assertStringNotContainsString('/wiki/events', $sitemap);
        $this->assertStringNotContainsString('/wiki/admin-event', $sitemap);
        $this->assertStringNotContainsString($origin.'/all-rebirths</loc>', $sitemap);
        $this->assertStringNotContainsString('/wiki/rebirths</loc>', $sitemap);
        $this->assertStringNotContainsString('/j8xq-4n2m-w9kp', $sitemap);
        $this->assertStringNotContainsString('x.sabex.lab', $sitemap);
        $this->assertStringNotContainsString('x.sabexistcount.com', $sitemap);
        $this->assertStringNotContainsString('trades.sabexistcount.com', $sitemap);
    }

    public function test_rarity_pages_do_not_mix_tiers_and_keep_other_on_catalog_only(): void
    {
        $game = $this->seedGame();
        foreach ([
            'common' => 'Common One',
            'rare' => 'Rare One',
            'epic' => 'Epic One',
            'legendary' => 'Legendary One',
            'mythic' => 'Mythic One',
            'brainrot god' => 'God One',
            'secret' => 'Secret One',
            'og' => 'Og One',
        ] as $rarity => $name) {
            $this->seedItem($game, [
                'slug' => str_replace(' ', '-', $rarity).'-one',
                'name' => $name,
                'rarity' => $rarity === 'brainrot god' ? 'Brainrot God' : ucfirst($rarity === 'og' ? 'OG' : $rarity),
            ]);
        }
        $this->seedItem($game, [
            'slug' => 'honey-other',
            'name' => 'Honey Other',
            'rarity' => 'Honey',
        ]);

        $catalog = $this->get('/wiki/all-brainrots');
        $catalog->assertOk();
        $catalog->assertSee('Honey Other', false);
        $catalog->assertSee('>Other<', false);
        $catalogHtml = (string) $catalog->getContent();
        $this->assertSame(9, substr_count($catalogHtml, '<tr data-wiki-row'));

        foreach (SabWikiPageDefinitions::RARITY_PAGE_SLUGS as $rarityKey => $slug) {
            $response = $this->get('/'.$slug);
            $response->assertOk();
            $html = (string) $response->getContent();
            $this->assertSame(1, substr_count($html, '<tr data-wiki-row'), $slug.' should list one row');
            $response->assertDontSee('Honey Other', false);
            $response->assertDontSee('name="keywords"', false);
            $schema = $this->wikiJsonLdFromHtml($html);
            $itemList = collect($schema['@graph'] ?? [])->firstWhere('@type', 'ItemList');
            $this->assertSame(1, $itemList['numberOfItems'] ?? null, $slug);
            foreach ($itemList['itemListElement'] ?? [] as $element) {
                $this->assertStringContainsString($rarityKey === 'brainrot god' ? 'god-one' : str_replace(' ', '-', $rarityKey).'-one', (string) ($element['url'] ?? ''));
            }
            foreach (SabWikiPageDefinitions::RARITY_PAGE_SLUGS as $otherKey => $otherSlug) {
                if ($otherSlug === $slug) {
                    continue;
                }
                $response->assertDontSee(match ($otherKey) {
                    'common' => 'Common One',
                    'rare' => 'Rare One',
                    'epic' => 'Epic One',
                    'legendary' => 'Legendary One',
                    'mythic' => 'Mythic One',
                    'brainrot god' => 'God One',
                    'secret' => 'Secret One',
                    'og' => 'Og One',
                    default => 'missing',
                }, false);
            }
        }

        $og = $this->get('/wiki/all-og-brainrots');
        $og->assertSee('Exist Count', false);
        $og->assertSee('Trade Value', false);
        $og->assertSee('Unknown', false);
        $og->assertSee('<h1 class="sab-wiki-title">All OG Brainrots</h1>', false);
        $og->assertSee('href="/wiki/all-brainrots"', false);
        $og->assertSee('aria-current="page">All OG Brainrots</span>', false);

        $catalogCrumbs = $this->get('/wiki/all-brainrots');
        $catalogCrumbs->assertSee('aria-current="page">All Brainrots</span>', false);

        $god = $this->get('/wiki/all-brainrot-god');
        $god->assertSee('<h1 class="sab-wiki-title">All Brainrot God Brainrots</h1>', false);
        $god->assertSee('not “Godly”', false);

        $rare = $this->get('/wiki/all-rare-brainrots');
        $rareHtml = (string) $rare->getContent();
        foreach ([
            'brainrot-rarity-common',
            'brainrot-rarity-rare',
            'brainrot-rarity-epic',
            'brainrot-rarity-legendary',
            'brainrot-rarity-mythic',
            'brainrot-rarity-brainrot-god',
            'brainrot-rarity-secret',
            'brainrot-rarity-og',
        ] as $class) {
            $this->assertStringContainsString($class, $rareHtml);
        }
        $this->assertStringContainsString('sab-wiki-tool brainrot-rarity-rare is-active', $rareHtml);
    }

    public function test_unshipped_event_paths_are_not_found_and_admin_abuse_skips_event_schema(): void
    {
        $game = $this->seedGame();
        $this->seedItem($game, [
            'slug' => 'admin-lucky-block',
            'name' => 'Admin Lucky Block',
            'rarity' => 'Secret',
            'attributes_json' => [
                'manual_update' => ['obtain_method' => 'Admin Abuse'],
            ],
        ]);
        $this->seedNews($game, [
            'slug' => 'saturday-update-unconfirmed',
            'title' => 'Saturday Update Unconfirmed',
            'published_at' => CarbonImmutable::parse('2026-08-29 14:00:00', 'America/New_York'),
        ]);

        $this->get('/wiki/events')->assertNotFound();
        $this->get('/wiki/admin-event')->assertNotFound();
        $this->get('/all-rebirths')->assertNotFound();
        $this->get('/wiki/rebirths')->assertNotFound();
        $this->get('/wiki/all-rebirths')->assertNotFound();
        $this->get('/all-brainrots')->assertNotFound();
        $this->get('/all-og-brainrots')->assertNotFound();
        $this->get('/all-fusions')->assertNotFound();
        $this->get('/all-lucky-blocks')->assertNotFound();

        $page = $this->get('/wiki/admin-abuse');
        $page->assertOk();
        $page->assertSee('<h1 class="sab-wiki-title">Steal a Brainrot Admin Abuse Time Today</h1>', false);
        $page->assertSee('Admin Lucky Block', false);
        $page->assertSee('Admin Abuse schedule', false);
        $page->assertSee('Quick Answer', false);
        $page->assertSee('not an exploit', false);
        $page->assertSee('What time is Admin Abuse in Steal a Brainrot today?', false);
        $page->assertSee('No timezone conversion is shown until a stored Admin Abuse or Taco Tuesday time exists.', false);
        $page->assertSee('Recent Saturday updates', false);
        $page->assertSee('Saturday Update Unconfirmed', false);
        $page->assertSee('href="/news/saturday-update-unconfirmed"', false);
        $page->assertDontSee('update-64', false);
        $this->assertSame(14, substr_count((string) $page->getContent(), '<details>'));
        $page->assertDontSee('"@type":"Event"', false);
        $page->assertDontSee('HowTo', false);
        $page->assertDontSee('every Saturday', false);
        $page->assertDontSee('taco cannon', false);
        $page->assertDontSee('Fat Sammy', false);
        $page->assertDontSee('Tipi Topi Taco', false);

        $this->get('/wiki/rituals')->assertNotFound();
        $this->get('/wiki/rituals.html')->assertNotFound();
        $this->get('/wiki/all-fusions')->assertNotFound();
        $this->get('/wiki/all-fusions.html')->assertNotFound();

        $rebirths = $this->get('/wiki/steal-a-brainrot-rebirth-list');
        $rebirths->assertOk();
        $rebirths->assertSee('<h1 class="sab-wiki-title">Steal a Brainrot Rebirth List — All 19 Levels</h1>', false);
        $rebirths->assertDontSee('No confirmed items are stored for this topic yet', false);
    }

    public function test_admin_abuse_page_renders_confirmed_schedule_event_schema_and_narrow_related_items(): void
    {
        $game = $this->seedGame();
        $game->forceFill(['settings_json' => [
            'sab_wiki' => [
                'admin_abuse' => [
                    'status' => 'confirmed',
                    'source_checked_at' => '2026-09-01T12:00:00+00:00',
                    'source_hash' => 'test-hash',
                    'admin_abuse' => [
                        'weekday' => 'Saturday',
                        'time' => '15:00',
                        'duration_min_minutes' => 30,
                        'duration_max_minutes' => 45,
                        'status' => 'confirmed',
                        'source' => 'manual',
                    ],
                    'taco_tuesday' => [
                        'weekday' => 'Tuesday',
                        'time' => '18:00',
                        'duration_min_minutes' => 30,
                        'duration_max_minutes' => 60,
                        'status' => 'confirmed',
                        'source' => 'manual',
                    ],
                    'mechanics' => ['Luck multipliers', 'Lucky Blocks'],
                ],
            ],
        ]])->save();
        $this->seedItem($game, [
            'slug' => 'admin-lucky-block',
            'name' => 'Admin Lucky Block',
            'rarity' => 'Secret',
            'attributes_json' => [],
        ]);
        $this->seedItem($game, [
            'slug' => 'taco-tuesday-brainrot',
            'name' => 'Taco Tuesday Brainrot',
            'rarity' => 'Legendary',
            'attributes_json' => ['manual_update' => ['obtain_method' => 'Taco Tuesday event']],
        ]);
        $this->seedItem($game, [
            'slug' => 'merchant-only',
            'name' => 'Merchant Only',
            'rarity' => 'Rare',
            'attributes_json' => ['manual_update' => ['obtain_method' => 'Merchant']],
        ]);
        $this->seedNews($game, [
            'slug' => 'saturday-update-newer',
            'title' => 'Saturday Update Newer',
            'published_at' => CarbonImmutable::parse('2026-08-29 14:00:00', 'America/New_York'),
        ]);
        $this->seedNews($game, [
            'slug' => 'saturday-update-older',
            'title' => 'Saturday Update Older',
            'published_at' => CarbonImmutable::parse('2026-08-22 14:00:00', 'America/New_York'),
        ]);
        $this->seedNews($game, [
            'slug' => 'taco-tuesday-note',
            'title' => 'Taco Tuesday Note',
            'published_at' => CarbonImmutable::parse('2026-08-18 10:00:00', 'America/New_York'),
        ]);
        $this->seedNews($game, [
            'slug' => 'how-to-read-trade-values',
            'title' => 'How to Read Trade Values',
            'published_at' => CarbonImmutable::parse('2026-08-19 12:00:00', 'America/New_York'),
        ]);
        $this->seedNews($game, [
            'slug' => 'saturday-browser-comparison-vs-other',
            'title' => 'Simulator vs Other Browser Games',
            'published_at' => CarbonImmutable::parse('2026-08-29 11:00:00', 'America/New_York'),
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 12:00:00', 'America/New_York'));
        try {
            $page = $this->get('/wiki/admin-abuse');
            $page->assertOk();
            $page->assertSee('<title>Steal a Brainrot Admin Abuse Time Today | Event Schedule</title>', false);
            $page->assertSee('<meta name="description" content="Find the next Steal a Brainrot Admin Abuse time, Taco Tuesday schedule, timezone conversions, event countdown, rewards, and confirmed updates." />', false);
            $page->assertSee('<link rel="canonical" href="https://sabexistcount.com/wiki/admin-abuse" />', false);
            $page->assertSee('<h1 class="sab-wiki-title">Steal a Brainrot Admin Abuse Time Today</h1>', false);
            $page->assertSee('Quick Answer', false);
            $page->assertSee('not an exploit', false);
            $page->assertSee('Next Admin Abuse', false);
            $page->assertSee('Sep 5, 2026 at 3:00 PM EDT', false);
            $page->assertSee('Next Taco Tuesday', false);
            $page->assertSee('Sep 1, 2026 at 6:00 PM EDT', false);
            $page->assertSee('Pacific', false);
            $page->assertSee('UTC', false);
            $page->assertSee('Singapore', false);
            $page->assertSee('Saturday Admin Abuse vs Taco Tuesday', false);
            $page->assertSee('How to join Admin Abuse', false);
            $page->assertSee('Late join', false);
            $page->assertSee('Lag during the window', false);
            $page->assertSee('Luck multipliers', false);
            $page->assertSee('Admin Lucky Block', false);
            $page->assertSee('Taco Tuesday Brainrot', false);
            $page->assertDontSee('Merchant Only', false);
            $page->assertDontSee('taco cannon', false);
            $page->assertDontSee('Fat Sammy', false);
            $page->assertDontSee('Tipi Topi Taco', false);
            $page->assertSee('data-admin-abuse-local', false);
            $page->assertSee('SAB Values', false);
            $page->assertSee('SAB Calculator', false);
            $page->assertSee('SAB Exist Count', false);
            $page->assertSee('Can you get banned for joining Admin Abuse?', false);
            $page->assertSee('Recent Saturday updates', false);
            $page->assertSee('Saturday Update Newer', false);
            $page->assertSee('Saturday Update Older', false);
            $page->assertSee('href="/news/saturday-update-newer"', false);
            $page->assertDontSee('news/taco-tuesday-note', false);
            $page->assertDontSee('how-to-read-trade-values', false);
            $page->assertDontSee('saturday-browser-comparison-vs-other', false);
            $page->assertDontSee('update-64', false);
            $page->assertSee('href="#saturday-updates"', false);

            $html = (string) $page->getContent();
            $newerPos = strpos($html, 'saturday-update-newer');
            $olderPos = strpos($html, 'saturday-update-older');
            $this->assertNotFalse($newerPos);
            $this->assertNotFalse($olderPos);
            $this->assertLessThan($olderPos, $newerPos);
            $this->assertSame(
                ['Saturday Update Newer', 'Saturday Update Older'],
                collect(app(SabRenderService::class)->wikiTopicViewContext(SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE)['saturdayUpdateNews'])->pluck('title')->all(),
            );
            $this->assertSame(1, substr_count($html, '<h1'));
            $this->assertSame(14, substr_count($html, '<details>'));
            $schema = $this->wikiJsonLdFromHtml($html);
            $this->assertSame(1, collect($schema['@graph'] ?? [])->where('@type', 'WebPage')->count());
            $this->assertCount(2, collect($schema['@graph'] ?? [])->where('@type', 'Event'));
            $faqSchema = collect($schema['@graph'] ?? [])->firstWhere('@type', 'FAQPage');
            $this->assertCount(14, $faqSchema['mainEntity'] ?? []);
            $this->assertSame(
                collect(app(SabRenderService::class)->wikiTopicViewContext(SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE)['wikiFaqItems'])->pluck('answer')->all(),
                collect($faqSchema['mainEntity'] ?? [])->map(fn (array $question): string => (string) ($question['acceptedAnswer']['text'] ?? ''))->all(),
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_rebirth_list_page_renders_guide_tables_images_and_schema(): void
    {
        $this->seedGame();

        $context = app(SabRenderService::class)->wikiTopicViewContext(SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS);
        $this->assertTrue($context['rebirthsGuide']);
        $this->assertCount(19, $context['rebirths']['levels'] ?? []);
        $this->assertSame('Rare', $context['rebirths']['levels'][0]['rarity'] ?? null);
        $this->assertSame('Secret', $context['rebirths']['levels'][15]['rarity'] ?? null);
        $this->assertSame('2026-09-01', $context['rebirths']['verified_at'] ?? null);
        $this->assertSame('Steal a Brainrot Rebirth List (Sep 2026) — All 19 Levels', $context['seoTitle']);
        $this->assertStringContainsString('Updated Sep 2026', $context['seoDescription']);
        $this->assertSame('Steal a Brainrot Rebirth List — All 19 Levels', $context['wikiH1']);
        $this->assertCount(11, $context['wikiFaqItems']);

        $response = $this->get('/wiki/steal-a-brainrot-rebirth-list');
        $response->assertOk();
        $response->assertSee('Steal a Brainrot Rebirth List (Sep 2026) — All 19 Levels', false);
        $response->assertSee('All 19 Steal a Brainrot Rebirth requirements and rewards', false);
        $response->assertSee('<h1 class="sab-wiki-title">Steal a Brainrot Rebirth List — All 19 Levels</h1>', false);
        $response->assertSee('19 Rebirth levels · Updated Sep 1, 2026', false);
        $response->assertSee('id="all-rebirth-requirements"', false);
        $response->assertSee('id="all-rebirth-rewards"', false);
        $response->assertSee('id="how-to-rebirth"', false);
        $response->assertSee('id="rebirth-tips"', false);
        $response->assertSee('id="rebirth-19"', false);
        $response->assertSee('$30Qa', false);
        $response->assertSee('La Grande Combinasion', false);
        $response->assertSee('Grief Shield', false);
        $response->assertSee('Los Tralaleritos', false);
        $response->assertSee('first Secret requirement', false);
        $response->assertSee('Should you move rare Brainrots to an alt before Rebirth?', false);
        $response->assertSee('>Rarity</th>', false);
        $response->assertSee('brainrot-rarity-secret', false);
        $response->assertSee('src="/uploads/images/sab/rebirths/rebirths-1-4.webp"', false);
        $response->assertSee('src="/uploads/images/sab/rebirths/rebirths-17-19.webp"', false);
        $response->assertSee('href="/sab-exist-count-list"', false);
        $response->assertSee('href="/sab-value-list"', false);
        $response->assertSee('href="/steal-a-brainrot-trading-calculator"', false);
        $response->assertSee('href="/products/trippi-troppi"', false);
        $response->assertSee('href="/products/gangster-footera"', false);
        $response->assertSee('href="/products/los-tralaleritos"', false);
        $response->assertSee('href="/products/la-grande-combinasion"', false);
        $response->assertSee('Use the <a href="/steal-a-brainrot-trading-calculator">SAB Trading Calculator</a> to check each name\'s current trade value before you consume it.', false);
        $html = (string) $response->getContent();
        $this->assertSame(19, substr_count($html, 'data-rebirth-requirement='));
        $this->assertSame(19, substr_count($html, 'data-rebirth-reward='));
        $this->assertSame(11, substr_count($html, '<details>'));
        $this->assertFileExists(public_path('uploads/images/sab/rebirths/rebirths-1-4.webp'));
        $this->assertFileExists(public_path('uploads/images/sab/rebirths/rebirth-19.webp'));

        $schema = $this->wikiJsonLdFromHtml($html);
        $faqSchema = collect($schema['@graph'] ?? [])->firstWhere('@type', 'FAQPage');
        $article = collect($schema['@graph'] ?? [])->firstWhere('@type', 'Article');
        $itemList = collect($schema['@graph'] ?? [])->firstWhere('@type', 'ItemList');
        $this->assertCount(11, $faqSchema['mainEntity'] ?? []);
        $this->assertSame(
            collect($context['wikiFaqItems'])->pluck('question')->all(),
            collect($faqSchema['mainEntity'] ?? [])->pluck('name')->all(),
        );
        $this->assertSame('Steal a Brainrot Rebirth List — All 19 Levels', $article['headline'] ?? null);
        $this->assertSame(19, $itemList['numberOfItems'] ?? null);
        $this->assertSame('Rebirth 19', $itemList['itemListElement'][18]['name'] ?? null);
        $this->assertStringContainsString('#rebirth-19', (string) ($itemList['itemListElement'][18]['url'] ?? ''));
    }

    public function test_wiki_reuses_existing_item_fields_and_does_not_write_game_settings(): void
    {
        $game = $this->seedGame();
        $this->assertNull($game->settings_json);
        $site = SeoSite::query()->where('slug', SabRenderService::SITE_SLUG)->firstOrFail();
        $this->assertSame([], data_get($site->settings_json, 'sab_wiki', []));

        $this->seedItem($game, [
            'slug' => 'locked-obtain',
            'name' => 'Locked Obtain',
            'rarity' => 'Rare',
            'attributes_json' => [
                'manual_update' => ['obtain_method' => 'Confirmed ritual'],
                'wiki_trivia' => ['note' => 'Stored trivia'],
            ],
        ]);

        $catalog = $this->get('/wiki/all-brainrots');
        $catalog->assertOk();
        $game->refresh();
        $site->refresh();
        $this->assertNull($game->settings_json);
        $this->assertSame([], data_get($site->settings_json, 'sab_wiki', []));
        $this->assertNull(data_get($game->fresh()->settings_json, 'sab_wiki'));

        $item = SeoItem::query()->where('slug', 'locked-obtain')->firstOrFail();
        $this->assertSame('Confirmed ritual', data_get($item->attributes_json, 'manual_update.obtain_method'));
        $this->assertSame('Stored trivia', data_get($item->attributes_json, 'wiki_trivia.note'));
        $this->assertNull(data_get($item->attributes_json, 'sab_wiki'));

        $catalogHtml = (string) $catalog->getContent();
        $this->assertStringContainsString('Locked Obtain', $catalogHtml);
        $this->assertStringContainsString('Confirmed ritual', $catalogHtml);
    }

    public function test_wiki_renders_all_local_catalog_images_and_itemlist_urls(): void
    {
        $game = $this->seedGame();
        $thumbDir = public_path('uploads/images/sab/thumbs');
        File::ensureDirectoryExists($thumbDir);
        $created = [];

        try {
            for ($index = 1; $index <= 21; $index++) {
                $slug = sprintf('wiki-thumb-%02d', $index);
                $this->seedItem($game, [
                    'slug' => $slug,
                    'name' => sprintf('Wiki Thumb %02d', $index),
                    'rarity' => 'Common',
                    'attributes_json' => [],
                ]);
                $path = $thumbDir.'/'.$slug.'-64.webp';
                File::put($path, 'webp');
                $created[] = $path;
            }

            $context = app(SabRenderService::class)->wikiCatalogViewContext('wiki/all-brainrots');
            $schema = $this->wikiJsonLdFromScript((string) $context['jsonLd']);
            $itemList = collect($schema['@graph'] ?? [])->firstWhere('@type', 'ItemList');

            $this->assertSame(21, $itemList['numberOfItems'] ?? null);
            $this->assertCount(21, $itemList['itemListElement'] ?? []);
            $this->assertSame('Wiki Thumb 01', $itemList['itemListElement'][0]['name'] ?? null);
            $this->assertSame('Wiki Thumb 21', $itemList['itemListElement'][20]['name'] ?? null);
            $this->assertTrue(collect($schema['@graph'] ?? [])->contains(fn (array $node): bool => ($node['@type'] ?? '') === 'FAQPage'));

            $response = $this->get('/wiki/all-brainrots');
            $response->assertOk();
            $html = (string) $response->getContent();

            $this->assertSame(21, substr_count($html, 'class="sab-wiki-thumb"'));
            $this->assertSame(0, substr_count($html, 'data-src="/uploads/images/sab/thumbs/'));
            $this->assertStringContainsString('src="/uploads/images/sab/thumbs/wiki-thumb-01-64.webp"', $html);
            $this->assertStringContainsString('src="/uploads/images/sab/thumbs/wiki-thumb-13-64.webp"', $html);
            $this->assertStringContainsString('Wiki Thumb 21', $html);
            $this->assertStringContainsString('href="/products/wiki-thumb-21"', $html);
            $this->assertStringContainsString('<img class="sab-wiki-thumb" src="/uploads/images/sab/thumbs/wiki-thumb-21-64.webp"', $html);
        } finally {
            foreach ($created as $path) {
                File::delete($path);
            }
        }
    }

    public function test_wiki_published_rarity_counts_ignore_unpublished_and_other_tiers(): void
    {
        $game = $this->seedGame();
        $this->seedItem($game, [
            'slug' => 'count-common',
            'name' => 'Count Common',
            'rarity' => 'Common',
        ]);
        $this->seedItem($game, [
            'slug' => 'count-og',
            'name' => 'Count OG',
            'rarity' => 'OG',
        ]);
        $this->seedItem($game, [
            'slug' => 'count-private',
            'name' => 'Count Private',
            'rarity' => 'Common',
            'is_publish_html' => false,
        ]);
        $this->seedItem($game, [
            'slug' => 'count-other',
            'name' => 'Count Other',
            'rarity' => 'Honey',
        ]);

        $counts = app(SabRenderService::class)->wikiPublishedRarityCounts();

        $this->assertSame(1, $counts['common']);
        $this->assertSame(1, $counts['og']);
        $this->assertSame(0, $counts['secret']);
        $this->assertArrayNotHasKey('other', $counts);
    }

    private function wikiJsonLdFromScript(string $script): array
    {
        $this->assertMatchesRegularExpression('/<script type="application\/ld\+json">/', $script);
        $json = preg_replace('/^<script type="application\/ld\+json">|<\/script>$/', '', $script) ?? '';

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function wikiJsonLdFromHtml(string $html): array
    {
        $this->assertSame(1, preg_match('/<script type="application\/ld\+json">(\{"@context":"https:\/\/schema.org","@graph":.*?\})<\/script>/s', $html, $matches));

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    private function seedGame(): SeoGame
    {
        $site = SeoSite::query()->updateOrCreate(
            ['slug' => SabRenderService::SITE_SLUG],
            [
                'name' => 'SAB Exist Count',
                'domain' => 'sabexistcount.com',
                'base_url' => 'https://sabexistcount.com',
                'output_path' => 'website/sab-exist-count',
                'settings_json' => SabRenderService::defaultSiteSettings(),
            ],
        );

        return SeoGame::query()->updateOrCreate(
            ['seo_site_id' => $site->id, 'slug' => 'steal-a-brainrot'],
            [
                'name' => 'Steal a Brainrot',
                'source_url' => '',
            ],
        );
    }

    private function seedSource(): SeoValueSource
    {
        return SeoValueSource::query()->create([
            'slug' => SabRotCalculatorSyncService::SOURCE_SLUG,
            'name' => 'Calculator source',
            'url' => 'https://example.test/calculator',
            'parser_type' => 'test',
            'priority' => 1,
            'is_primary_source' => true,
            'enabled' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function seedNews(SeoGame $game, array $overrides): SeoNewsArticle
    {
        return SeoNewsArticle::query()->create(array_merge([
            'seo_site_id' => $game->seo_site_id,
            'type' => SeoNewsArticle::TYPE_NEWS,
            'slug' => 'weekly-note',
            'locale' => 'en',
            'title' => 'Weekly Note',
            'excerpt' => 'Note.',
            'body_html' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => '2026-08-29 12:00:00',
            'meta_title' => 'Weekly Note',
            'meta_description' => 'Note.',
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function seedItem(SeoGame $game, array $overrides): SeoItem
    {
        return SeoItem::query()->create(array_merge([
            'seo_game_id' => $game->id,
            'slug' => 'brainrot',
            'name' => 'Brainrot',
            'rarity' => 'Common',
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

    private function seedValue(SeoItem $item, SeoValueSource $source, float $value, string $changedAt): SeoItemVariant
    {
        $variant = SeoItemVariant::query()->create([
            'seo_item_id' => $item->id,
            'variant_key' => 'base',
            'variant_name' => 'Base',
            'variant_type' => 'base',
            'sort_order' => 0,
        ]);

        SeoItemCurrentValue::query()->create([
            'seo_item_variant_id' => $variant->id,
            'seo_value_source_id' => $source->id,
            'collected_at' => $changedAt,
            'changed_at' => $changedAt,
            'exist_count_raw' => '',
            'exist_count_normalized' => null,
            'value_raw' => (string) $value,
            'value_normalized' => $value,
            'currency' => 'ROBUX',
            'demand' => '',
            'confidence' => 90,
            'source_payload_hash' => hash('sha256', $item->slug.':'.$value),
        ]);

        return $variant;
    }

    private function seedObservation(SeoItemVariant $variant, SeoValueSource $source, float $value, $observedAt): void
    {
        SeoItemObservation::query()->create([
            'seo_item_variant_id' => $variant->id,
            'seo_value_source_id' => $source->id,
            'observed_at' => $observedAt,
            'value_raw' => (string) $value,
            'value_normalized' => $value,
            'currency' => 'ROBUX',
            'demand' => 'high',
            'confidence' => 90,
            'source_payload_hash' => hash('sha256', $variant->id.':'.$value.':'.$observedAt),
        ]);
    }
}
