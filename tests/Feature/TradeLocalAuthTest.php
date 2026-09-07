<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoSite;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradeLocalAuthTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sab.hosts.www' => 'www.sabex.lab',
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
    }

    public function test_local_register_logs_in_and_can_open_post(): void
    {
        $this->get('http://www.sabex.lab/auth/roblox')
            ->assertOk()
            ->assertDontSee('Local login')
            ->assertDontSee('Local register');

        $this->post('http://www.sabex.lab/auth/local/register', [
            'username' => 'Alice_1',
            'password' => 'password1',
            'display_name' => 'Alice',
        ])->assertRedirect('/trading');

        $user = TradeUser::query()->first();
        $this->assertNotNull($user);
        $this->assertSame('local:alice_1', $user->roblox_sub);
        $this->assertFalse($user->posting_approved);
        $this->assertNull($user->posting_approved_at);
        $this->assertAuthenticatedAs($user, 'trades');

        $this->get('http://www.sabex.lab/trading/new')
            ->assertOk()
            ->assertSee('Create Trade Ad')
            ->assertSee('I Have')
            ->assertSee('I Want')
            ->assertSee('Publish Trade Ad')
            ->assertSee('data-publish-pending', false);

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.can_post', false);
    }

    public function test_local_login_with_password(): void
    {
        $this->post('http://www.sabex.lab/auth/local/register', [
            'username' => 'bob',
            'password' => 'password1',
        ])->assertRedirect('/trading');

        $this->post('http://www.sabex.lab/logout')->assertRedirect('/trading');
        $this->assertGuest('trades');

        $this->post('http://www.sabex.lab/auth/local/login', [
            'username' => 'bob',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->post('http://www.sabex.lab/auth/local/login', [
            'username' => 'bob',
            'password' => 'password1',
        ])->assertRedirect('/trading');

        $this->assertAuthenticated('trades');
    }

    public function test_production_hides_local_forms_and_returns_404(): void
    {
        $this->app['env'] = 'production';
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->get('http://www.sabex.lab/auth/roblox')
            ->assertOk()
            ->assertDontSee('Local login')
            ->assertDontSee('Local register')
            ->assertSee('Under development.');

        $this->from('http://www.sabex.lab/auth/roblox')
            ->post('http://www.sabex.lab/auth/local/register', [
                'username' => 'alice',
                'password' => 'password1',
            ])->assertNotFound();

        $this->from('http://www.sabex.lab/auth/roblox')
            ->post('http://www.sabex.lab/auth/local/login', [
                'username' => 'alice',
                'password' => 'password1',
            ])->assertNotFound();
    }
}
