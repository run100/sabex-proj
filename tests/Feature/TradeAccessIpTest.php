<?php

namespace Tests\Feature;

use App\Models\AccessLog;
use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoSite;
use App\Models\SeoUser;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradeAccessIpTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;
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
            'slug' => 'noobini',
            'name' => 'Noobini',
            'rarity' => 'Common',
            'description' => '',
            'summary' => '',
            'is_listed' => true,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '10',
            'total_exists' => 5,
            'attributes_json' => [
                'rot_rocks' => [
                    'name' => 'Noobini',
                    'base_income' => 10,
                    'robux_value' => 10,
                    'demand' => 'HIGH',
                ],
            ],
            'sort_order' => 0,
        ]);
        SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => 'cappuccino',
            'name' => 'Cappuccino',
            'rarity' => 'Common',
            'description' => '',
            'summary' => '',
            'is_listed' => true,
            'image_url' => '',
            'local_image_url' => '',
            'avg_coins_raw' => '12',
            'total_exists' => 5,
            'attributes_json' => [
                'rot_rocks' => [
                    'name' => 'Cappuccino',
                    'base_income' => 10,
                    'robux_value' => 12,
                    'demand' => 'HIGH',
                ],
            ],
            'sort_order' => 0,
        ]);
    }

    public function test_register_login_and_publish_write_ips_and_logs(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post('http://www.sabex.lab/auth/local/register', [
                'username' => 'ipuser',
                'password' => 'password1',
            ])
            ->assertRedirect('/trading');

        $user = TradeUser::query()->first();
        $this->assertSame('203.0.113.10', $user->registered_ip);
        $this->assertSame('203.0.113.10', $user->last_login_ip);
        $this->assertSame(1, AccessLog::query()->where('action', 'register')->where('actor_id', $user->id)->count());
        $this->assertSame(1, AccessLog::query()->where('action', 'login')->where('actor_id', $user->id)->count());

        $this->post('http://www.sabex.lab/logout')->assertRedirect('/trading');
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->post('http://www.sabex.lab/auth/local/login', [
                'username' => 'ipuser',
                'password' => 'password1',
            ])
            ->assertRedirect('/trading');
        $user->refresh();
        $this->assertSame('203.0.113.10', $user->registered_ip);
        $this->assertSame('198.51.100.20', $user->last_login_ip);
        $this->assertSame(2, AccessLog::query()->where('action', 'login')->where('actor_id', $user->id)->count());

        $admin = SeoUser::factory()->create();
        $this->actingAs($admin, 'admin')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->patchJson('http://x.sabex.lab/api/trade-users/'.$user->id, [
                'posting_approved' => true,
            ])
            ->assertOk();
        $user->refresh();

        $this->actingAs($user, 'trades')
            ->withServerVariables(['REMOTE_ADDR' => '192.0.2.55'])
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [['slug' => 'noobini']],
                'looking_for' => [['slug' => 'cappuccino']],
            ])
            ->assertCreated();

        $listing = TradeListing::query()->first();
        $this->assertSame('192.0.2.55', $listing->posted_ip);
        $this->assertSame(1, AccessLog::query()->where('action', 'publish')->where('subject_id', $listing->id)->count());

        $this->actingAs($admin, 'admin')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('http://x.sabex.lab/api/trade-listings/'.$listing->id)
            ->assertOk()
            ->assertJsonPath('listing.posted_ip', '192.0.2.55');

        $this->actingAs($admin, 'admin')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('http://x.sabex.lab/api/trade-users')
            ->assertOk()
            ->assertJsonPath('users.0.registered_ip', '203.0.113.10')
            ->assertJsonPath('users.0.last_login_ip', '198.51.100.20');

        $this->actingAs($admin, 'admin')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('http://x.sabex.lab/api/access-logs')
            ->assertOk()
            ->assertJsonPath('logs.0.action', 'publish');
    }
}
