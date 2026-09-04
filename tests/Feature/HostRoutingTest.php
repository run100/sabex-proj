<?php

namespace Tests\Feature;

use App\Models\User;
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
            'sab.hosts.trades' => 'trades.sabex.lab',
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

    public function test_admin_host_without_ip_is_404_and_login_works_from_allowlist(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get('http://x.sabex.lab/login')
            ->assertNotFound();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('http://x.sabex.lab/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Sign in');

        $this->get('http://x.sabex.lab/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /');

        $this->get('http://x.sabex.lab/items')->assertRedirect('/login');
    }

    public function test_admin_login_and_item_toggle(): void
    {
        $this->seedPublicSite();
        $user = User::factory()->create();
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

        $this->actingAs($user)
            ->get('http://x.sabex.lab/api/items')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'admin-item']);

        $this->actingAs($user)
            ->patchJson('http://x.sabex.lab/api/items/'.$item->id, ['is_listed' => true, 'is_publish_html' => true])
            ->assertOk()
            ->assertJsonPath('item.is_listed', true);
    }

    public function test_trades_host_lists_and_disallows_post_in_robots(): void
    {
        $this->get('http://trades.sabex.lab/')
            ->assertOk()
            ->assertSee('Recent Trades');

        $this->get('http://trades.sabex.lab/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /post')
            ->assertSee('Disallow: /auth/');
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
