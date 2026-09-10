<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoNewsArticle;
use App\Models\SeoSite;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class SeoCacheAndNavTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sab.hosts.www' => 'www.sabex.lab']);
        $this->createSeoTables();
        $this->createTradeTables();
        SeoSite::query()->create([
            'slug' => SabRenderService::SITE_SLUG,
            'name' => 'SAB',
            'domain' => 'sabexistcount.com',
            'base_url' => 'https://sabexistcount.com',
            'output_path' => '',
            'settings_json' => [],
        ]);
        SeoGame::query()->create([
            'seo_site_id' => 1,
            'slug' => 'steal-a-brainrot',
            'name' => 'Steal a Brainrot',
        ]);
        SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'cache-nav-item',
            'name' => 'Cache Nav Item',
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
        ]);
        SeoNewsArticle::query()->create([
            'seo_site_id' => 1,
            'type' => SeoNewsArticle::TYPE_STATIC_PAGE,
            'slug' => 'about-us',
            'locale' => 'en',
            'title' => 'About us',
            'excerpt' => '',
            'cover_image_url' => '',
            'body_html' => '<p>About</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'meta_title' => '',
            'meta_description' => '',
            'sort_order' => 1,
            'is_system_log' => false,
        ]);
    }

    public function test_seo_pages_are_cdn_cacheable_and_omit_session_cookies(): void
    {
        $paths = [
            '/',
            '/wiki',
            '/products/cache-nav-item',
            '/steal-a-brainrot-trading-calculator',
            '/es/steal-a-brainrot-trading-calculator',
            '/sab-value-list',
            '/sab-exist-count-list',
            '/steal-a-brainrot-codes',
            '/games',
            '/about-us',
        ];

        foreach ($paths as $path) {
            $response = $this->get('http://www.sabex.lab'.$path);
            $response->assertOk();
            $this->assertPublicSeoCdn($response);
            $response->assertSee('/static/js/sab-nav-auth.js', false);
            $response->assertSee('data-nav-auth', false);
            $response->assertSee('data-nav-sign-in', false);
            $response->assertSee('data-roblox-auth-modal', false);
            $response->assertDontSee('name="csrf-token"', false);
            $response->assertDontSee('_token', false);
        }

        $calculator = $this->get('http://www.sabex.lab/steal-a-brainrot-trading-calculator');
        $calculator->assertOk();
        $this->assertCalculatorBuilderOmitsCsrf($calculator->getContent());
        $calculator->assertSee('Create a Trade')
            ->assertSee('View Trades')
            ->assertSee('href="'.\App\Support\TradePaths::create().'"', false)
            ->assertSee('href="'.\App\Support\TradePaths::marketplace().'"', false)
            ->assertSee('sab-calc-trade-pills', false);
    }

    public function test_home_value_list_and_calculator_promote_live_trade_ads(): void
    {
        foreach (['/', '/sab-value-list', '/steal-a-brainrot-trading-calculator'] as $path) {
            $this->get('http://www.sabex.lab'.$path)
                ->assertOk()
                ->assertSee('New · Trade ads are live — browse open Steal a Brainrot trades')
                ->assertSee('href="'.\App\Support\TradePaths::marketplace().'"', false)
                ->assertSee('data-sab-trades-launch-notice', false)
                ->assertDontSee('New · Steal a Brainrot Admin Abuse Time Today');
        }

        $calculator = $this->get('http://www.sabex.lab/steal-a-brainrot-trading-calculator');
        $calculator->assertOk()
            ->assertDontSee('News · Aug 19')
            ->assertDontSee('Headless Horseman rebounds');
        $this->assertSame(1, substr_count($calculator->getContent(), 'New · Trade ads are live — browse open Steal a Brainrot trades'));

        $promo = view('seo.sab.partials._promo-calculator')->render();
        $this->assertStringContainsString('Open Trades', $promo);
        $this->assertStringContainsString('New: Trade ads are live — post what you have or browse open trades', $promo);
        $this->assertStringContainsString('href="'.\App\Support\TradePaths::marketplace().'"', $promo);
        $this->assertStringContainsString('sab_trades_promo_closed', $promo);
        $this->assertStringNotContainsString('SABExistCount Calculator', $promo);
        $this->assertStringNotContainsString('sab_calc_promo_closed', $promo);
    }

    public function test_logged_in_seo_html_stays_anonymous(): void
    {
        $user = TradeUser::query()->create([
            'roblox_sub' => '88001',
            'roblox_user_id' => '88001',
            'username' => 'cachenavuserxyz',
            'display_name' => 'CacheNavUserXYZ',
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'last_login_at' => now(),
        ]);

        $home = $this->actingAs($user, 'trades')->get('http://www.sabex.lab/');
        $home->assertOk();
        $this->assertPublicSeoCdn($home);
        $home->assertDontSee('CacheNavUserXYZ');
        $home->assertDontSee('cachenavuserxyz');
        $home->assertSee('data-nav-sign-in', false);
        $home->assertSee('/static/js/sab-nav-auth.js', false);

        $wiki = $this->actingAs($user, 'trades')->get('http://www.sabex.lab/wiki');
        $wiki->assertOk();
        $this->assertPublicSeoCdn($wiki);
        $wiki->assertDontSee('CacheNavUserXYZ');
        $wiki->assertSee('/static/js/sab-nav-auth.js', false);
    }

    public function test_calculator_stays_cdn_cacheable_when_session_cookie_is_present(): void
    {
        $sessionCookie = (string) config('session.cookie');
        $this->assertNotSame('', $sessionCookie);

        $response = $this->withCookie($sessionCookie, 'seo-cdn-session')
            ->get('http://www.sabex.lab/steal-a-brainrot-trading-calculator');
        $response->assertOk();
        $this->assertPublicSeoCdn($response);
        $response->assertDontSee('name="csrf-token"', false);
        $response->assertDontSee('_token', false);
        $this->assertCalculatorBuilderOmitsCsrf($response->getContent());
        $response->assertSee('/static/js/sab-nav-auth.js', false);
    }

    public function test_home_and_trading_share_full_header_nav_and_language_switch(): void
    {
        foreach (['/', '/trading'] as $path) {
            $response = $this->get('http://www.sabex.lab'.$path);
            $response->assertOk()
                ->assertSee('sab-site-header', false)
                ->assertSee('sab-wiki-drawer-trigger', false)
                ->assertSee('SAB<span class="sab-brand-accent">ExistCount</span>.com', false)
                ->assertSee('color: #67e8f9', false)
                ->assertSee('/static/css/sab-tokens.css', false)
                ->assertSee('sab-main-nav sab-main-nav--desktop', false)
                ->assertSee('sab-main-nav sab-main-nav--compact', false)
                ->assertSee('sab-trading-nav', false)
                ->assertSee('sab-nav-badge--hot', false)
                ->assertSee('sab-nav-badge--new', false)
                ->assertSee('sab-bottom-nav', false)
                ->assertSee('sab-bottom-nav__item', false)
                ->assertSee('Trade Ads')
                ->assertSee('SAB Values')
                ->assertSee('>HOT</span>', false)
                ->assertSee('>NEW</span>', false)
                ->assertSee('Values')
                ->assertSee('Trades')
                ->assertSee('Calculator')
                ->assertSee('Guides')
                ->assertSee('Wiki')
                ->assertSee('News')
                ->assertSee('Home')
                ->assertSee('More')
                ->assertSee('Codes')
                ->assertSee('Exist Count List')
                ->assertSee('>Login</a>', false)
                ->assertSee(SabRenderService::PAGE_CODES, false)
                ->assertSee(SabRenderService::PAGE_VALUE_LIST, false)
                ->assertSee('/wiki', false)
                ->assertSee('data-sab-language-switch', false)
                ->assertSee('value="/pt"', false)
                ->assertSee('>PT</option>', false)
                ->assertSee('Create Trade Ad')
                ->assertSee('View Trade Ads')
                ->assertSee('View Offers')
                ->assertSee(\App\Support\TradePaths::pending(), false)
                ->assertSee('>Create Trade Ad</span>', false)
                ->assertSee('>View Trade Ads</span>', false)
                ->assertDontSee('>Post</span>', false)
                ->assertDontSee('>Activity</span>', false);

            preg_match('/class="sab-main-nav sab-main-nav--desktop"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $desktopNav);
            $this->assertNotSame('', $desktopNav[1] ?? '');
            $this->assertStringContainsString('sab-trading-nav', $desktopNav[1]);
            $this->assertStringNotContainsString('Exist Count Gallery', $desktopNav[1]);
        }

        $navJs = (string) file_get_contents(public_path('static/js/sab-nav-auth.js'));
        $this->assertStringContainsString("var ME_URL = '/api/v1/me'", $navJs);
        $this->assertStringNotContainsString('hideTradesNavExtras', $navJs);
        $this->assertSame(1, preg_match('/function renderHeader[\s\S]+function renderDrawer/', $navJs, $headerFn));
        $this->assertStringContainsString('function quickLinks(openCount)', $navJs);
        $this->assertStringContainsString('function tradesBadge(openCount)', $navJs);
        $this->assertStringContainsString('open_listings_count', $navJs);
        $this->assertStringContainsString('Trades, ', $navJs);
        $this->assertStringContainsString("icon('calculator')", $navJs);
        $this->assertStringContainsString("icon('trades')", $navJs);
        $this->assertStringContainsString('<path d="M8 3 4 7l4 4"/>', $navJs);
        $this->assertStringContainsString('sab-nav-auth__quick-link--calculator', $navJs);
        $this->assertStringContainsString('/steal-a-brainrot-trading-calculator', $navJs);
        $this->assertStringContainsString('href="/trading"', $navJs);
        $this->assertStringContainsString('title="Browse and post Steal a Brainrot trade ads"', $navJs);
        $this->assertStringContainsString('function isTradesPath(path)', $navJs);
        $this->assertStringContainsString("path === '/trading'", $navJs);
        $this->assertStringContainsString("path.indexOf('/trading/') === 0", $navJs);
        $this->assertStringContainsString('steal-a-brainrot-trading-calculator', $navJs);
        $this->assertStringContainsString(' is-active', $navJs);
        $this->assertStringContainsString('aria-current="page', $navJs);
        $this->assertLessThan(strpos($navJs, 'href="/steal-a-brainrot-trading-calculator"'), strpos($navJs, 'href="/trading"'));
        $this->assertStringContainsString("icon('bell')", $headerFn[0]);
        $this->assertStringContainsString('aria-label="Alerts"', $headerFn[0]);
        $this->assertStringContainsString('data-sab-account-nav', $headerFn[0]);
        $this->assertStringContainsString('Profile', $headerFn[0]);
        $this->assertStringContainsString('Sign out', $headerFn[0]);
        $this->assertStringContainsString('data-nav-sign-out', $headerFn[0]);
        $this->assertStringNotContainsString('Last seen', $headerFn[0]);
        $headerView = (string) file_get_contents(resource_path('views/seo/sab/partials/_header.blade.php'));
        $this->assertStringContainsString('.sab-account-nav__menu', $headerView);
        $this->assertStringContainsString('.sab-nav-auth__quick-link', $headerView);
        $this->assertStringContainsString('.sab-nav-auth__quick-link--calculator svg', $headerView);
        $this->assertStringContainsString('.sab-nav-auth__link.is-active', $headerView);
        $this->assertStringContainsString('color: #67e8f9;', $headerView);
        $this->assertStringContainsString('#fb7185', $headerView);
        $this->assertSame(1, preg_match('/function renderDrawer[\s\S]+function renderBar/', $navJs, $drawerFn));
        $this->assertStringNotContainsString("'Account'", $drawerFn[0]);
    }

    public function test_trading_and_me_are_private_no_store(): void
    {
        $trading = $this->get('http://www.sabex.lab/trading')
            ->assertOk()
            ->assertSee('/static/js/sab-nav-auth.js', false)
            ->assertSee('data-nav-sign-in', false)
            ->assertSee('No matching trades yet.');
        $this->assertPrivateNoStore($trading);

        $auth = $this->get('http://www.sabex.lab/auth/roblox')->assertOk();
        $this->assertPrivateNoStore($auth);

        $user = TradeUser::query()->create([
            'roblox_sub' => '88002',
            'roblox_user_id' => '88002',
            'username' => 'cacheprofilexyz',
            'display_name' => 'CacheProfileXYZ',
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'last_login_at' => now(),
        ]);
        $profile = $this->get('http://www.sabex.lab/profile/'.$user->profile_id)->assertOk();
        $this->assertPrivateNoStore($profile);

        $me = $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user', null)
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.open_listings_count', 0);
        $this->assertPrivateNoStore($me);
    }

    public function test_local_pages_show_email_login_in_shared_modal(): void
    {
        $this->app['env'] = 'local';

        foreach (['/', '/steal-a-brainrot-trading-calculator', '/trading/new', '/auth/roblox'] as $path) {
            $this->get('http://www.sabex.lab'.$path)
                ->assertOk()
                ->assertSee('data-roblox-auth-modal', false)
                ->assertSee('Configure Roblox OAuth')
                ->assertSee('Continue with Roblox')
                ->assertSee('border: 1px solid rgba(148, 163, 184, 0.4)')
                ->assertSee('Email login')
                ->assertSee('Create an account')
                ->assertSee('>Login</a>', false);
        }
    }

    public function test_calculator_only_header_uses_shared_modal(): void
    {
        $html = view('seo.sab.partials._header', [
            'locale' => 'en',
            'urlPrefix' => '',
            't' => [],
            'calculatorOnly' => true,
            'brand' => ['logo_html' => 'SAB<span class="sab-brand-accent">Calculator</span>.com'],
            'languageLinks' => [],
        ])->render();

        $this->assertStringContainsString('data-roblox-auth-modal', $html);
        $this->assertStringContainsString('Calculator', $html);
        $this->assertStringContainsString('>Login</a>', $html);
        $this->assertStringNotContainsString('name="csrf-token"', $html);
        $this->assertStringNotContainsString('_token', $html);
    }

    public function test_calculator_layout_includes_shared_header_and_modal(): void
    {
        $html = view('seo.sab.layout-calculator', [
            'locale' => 'en',
            'seoTitle' => 'Calculator',
            'seoDescription' => 'Compare trades',
            'canonical' => 'http://www.sabex.lab/',
            'urlPrefix' => '',
            't' => [],
            'calculatorOnly' => true,
            'brand' => [
                'logo_html' => 'SABCalculator',
                'og_site_name' => 'SAB Calculator',
                'site_name' => 'SAB Calculator',
            ],
            'hreflangLinks' => [],
            'languageLinks' => [],
            'cssHref' => '/static/css/sabcalculator.css',
        ])->render();

        $this->assertStringContainsString('data-roblox-auth-modal', $html);
        $this->assertStringContainsString('data-nav-sign-in', $html);
        $this->assertStringContainsString('/static/js/sab-nav-auth.js', $html);
        $this->assertStringContainsString('sab-wiki-drawer', $html);
        $this->assertStringContainsString('sab-bottom-nav', $html);
        $this->assertStringNotContainsString('Create a Trade', $html);
        $this->assertStringNotContainsString('View Trades', $html);
        $this->assertStringNotContainsString('sab-calc-trade-pills', $html);
    }

    private function assertPublicSeoCdn($response): void
    {
        $this->assertStringContainsString('s-maxage=14400', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('max-age=14400', $response->headers->get('Cloudflare-CDN-Cache-Control'));
        $this->assertSame('Accept-Encoding', $response->headers->get('Vary'));
        $this->assertFalse($response->headers->has('Set-Cookie'));
    }

    private function assertPrivateNoStore($response): void
    {
        $cacheControl = strtolower((string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    private function assertCalculatorBuilderOmitsCsrf(string $html): void
    {
        $this->assertSame(1, preg_match(
            '/<script type="application\/json" id="sab-trade-builder-config">(.*?)<\/script>/s',
            $html,
            $match
        ));
        $config = json_decode($match[1], true);
        $this->assertIsArray($config);
        $this->assertSame('', $config['csrf'] ?? null);
        $this->assertNull($config['data'] ?? null);
        $this->assertSame('/data/calc/sab/manifest.json', $config['catalogManifestUrl'] ?? null);
    }
}
