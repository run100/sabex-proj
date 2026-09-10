<?php

namespace Tests\Feature;

use App\Models\SeoUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\TestCase;

class HostRoutingTest extends TestCase
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
    }

    public function test_localhost_keeps_public_home_and_hidden_console_path_is_404(): void
    {
        $this->seedPublicSite();
        $this->get('/')->assertOk();
        $this->get('/'.config('sab.console_path'))->assertNotFound();
    }

    public function test_admin_host_without_ip_is_403_and_login_works_from_allowlist(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get('http://x.sabex.lab/login')
            ->assertForbidden()
            ->assertSee('Forbidden');

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('http://x.sabex.lab/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Sign in');

        $this->get('http://x.sabex.lab/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /');

        $this->get('http://x.sabex.lab/items')->assertRedirect('/login');
        $this->get('http://x.sabex.lab/users')->assertRedirect('/login');
        $this->get('http://x.sabex.lab/listings')->assertRedirect('/login');
        $this->get('http://x.sabex.lab/logs')->assertRedirect('/login');
    }

    public function test_admin_spa_serves_console_paths_when_authenticated(): void
    {
        $admin = SeoUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get('http://x.sabex.lab/users')
            ->assertOk()
            ->assertSee('Admin-SAB');

        $this->actingAs($admin, 'admin')
            ->get('http://x.sabex.lab/listings')
            ->assertOk()
            ->assertSee('Admin-SAB');

        $this->actingAs($admin, 'admin')
            ->get('http://x.sabex.lab/logs')
            ->assertOk()
            ->assertSee('Admin-SAB');

        $this->actingAs($admin, 'admin')
            ->get('http://x.sabex.lab/sites')
            ->assertOk()
            ->assertSee('Admin-SAB');
    }

    public function test_admin_login_and_item_toggle(): void
    {
        $this->seedPublicSite();
        $user = SeoUser::factory()->create();
        $item = \App\Models\SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'admin-item',
            'name' => 'Admin Item',
            'rarity' => 'Common',
            'description' => '',
            'summary' => '',
            'is_publish_html' => false,
            'is_listed' => false,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '',
            'attributes_json' => [],
            'sort_order' => 0,
        ]);

        $this->actingAs($user, 'admin')
            ->get('http://x.sabex.lab/api/items')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'admin-item']);

        $this->actingAs($user, 'admin')
            ->patchJson('http://x.sabex.lab/api/items/'.$item->id, ['is_listed' => true, 'is_publish_html' => true])
            ->assertOk()
            ->assertJsonPath('item.is_listed', true);
    }

    public function test_admin_login_authenticates_seo_users(): void
    {
        $user = SeoUser::factory()->create([
            'email' => 'nara.kestrel.84@sabex.lab',
            'password' => 'password',
        ]);

        $this->post('http://x.sabex.lab/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertNotFound();

        $this->post('http://x.sabex.lab/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/items');

        $this->assertAuthenticatedAs($user, 'admin');
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotEmpty($user->last_login_ip);
        $this->assertSame(1, \App\Models\AccessLog::query()->where('action', 'login')->where('actor_type', 'admin')->count());
    }

    public function test_www_trading_lists_and_robots_only_declares_sitemap(): void
    {
        $this->get('http://www.sabex.lab/trading')
            ->assertOk()
            ->assertSee('<title>Steal a Brainrot Trades – Live Trade Ads | SABExistCount</title>', false)
            ->assertSee('<h1>Steal a Brainrot Trades</h1>', false)
            ->assertSee('trades-show__back', false)
            ->assertSee('All Trades')
            ->assertSee('Browse live Steal a Brainrot trade ads, post your own offer', false)
            ->assertSee('Browse live Steal a Brainrot trades posted by the community', false)
            ->assertSee('<h2 class="trades-home-list-title">Recent Steal a Brainrot Trades</h2>', false)
            ->assertSee('<h2 class="text-xl font-black">About Steal a Brainrot trades</h2>', false)
            ->assertSee('<h2 class="text-xl font-black">Trade FAQ</h2>', false)
            ->assertDontSee('<h2 id="trades-auth-modal-title"', false)
            ->assertSee('<p id="trades-auth-modal-title"', false)
            ->assertDontSee('Steal a Brainrot Trade Calculator &amp; Value List | SABExistCount', false)
            ->assertDontSee('<h1>Steal a Brainrot Trade Calculator</h1>', false)
            ->assertSee('Filter Trades')
            ->assertSee('trades-filter__panel', false)
            ->assertDontSee('trades-filter__panel is-open', false)
            ->assertSee('Brainrot I want to get')
            ->assertSee('Brainrot I have to give')
            ->assertSee('Post a Trade')
            ->assertSee('trades-home-actions__row', false)
            ->assertSee('Offers')
            ->assertSee('Value Calculator')
            ->assertSee('Value List')
            ->assertSee('href="/user/offers"', false)
            ->assertSee('href="/steal-a-brainrot-trading-calculator"', false)
            ->assertSee('href="/sab-value-list"', false)
            ->assertDontSee('See Activity')
            ->assertSee('What is Steal a Brainrot trades?')
            ->assertSee('Is trading on SABExistCount free?')
            ->assertSee('FAQPage', false)
            ->assertSee('BreadcrumbList', false)
            ->assertSee('"name":"Trades"', false)
            ->assertSee('/trading', false)
            ->assertDontSee('Open Post a Trade');

        $this->get('http://www.sabex.lab/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap:')
            ->assertSee('/sitemap.xml')
            ->assertDontSee('Disallow: /post')
            ->assertDontSee('Disallow: /auth/');
    }

    private function seedPublicSite(): void
    {
        \App\Models\SeoSite::query()->create([
            'slug' => \App\Services\Seo\SabRenderService::SITE_SLUG,
            'name' => 'SAB',
            'domain' => 'sabexistcount.com',
            'base_url' => 'https://sabexistcount.com',
            'output_path' => '',
            'settings_json' => [],
        ]);
        \App\Models\SeoGame::query()->create([
            'seo_site_id' => 1,
            'slug' => 'steal-a-brainrot',
            'name' => 'Steal a Brainrot',
        ]);
    }
}
