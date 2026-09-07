<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoSite;
use App\Services\Seo\SabRenderService;
use App\Support\TradePresenter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradeRobloxAuthTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sab.hosts.www' => 'www.sabex.lab',
            'services.roblox.client_id' => '',
            'services.roblox.client_secret' => '',
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

    public function test_unconfigured_post_redirects_to_login_instead_of_404(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post('http://www.sabex.lab/auth/roblox')
            ->assertRedirect('/auth/roblox')
            ->assertSessionHas('trade_error', TradePresenter::oauthUnavailableCopy());
    }

    public function test_local_login_page_explains_oauth_setup(): void
    {
        $this->app['env'] = 'local';

        $this->get('http://www.sabex.lab/auth/roblox')
            ->assertOk()
            ->assertSee('Configure Roblox OAuth')
            ->assertSee('ROBLOX_CLIENT_ID')
            ->assertSee('Email login')
            ->assertSee('Create an account')
            ->assertSee('Continue with Roblox');
    }

    public function test_production_login_page_says_under_development(): void
    {
        $this->app['env'] = 'production';

        $this->get('http://www.sabex.lab/auth/roblox')
            ->assertOk()
            ->assertSee('Under development.')
            ->assertDontSee('ROBLOX_CLIENT_ID')
            ->assertDontSee('Continue with Roblox')
            ->assertDontSee('Email login')
            ->assertDontSee('Create an account');
    }

    public function test_apex_lab_host_redirects_to_www(): void
    {
        $this->get('http://sabex.lab/auth/roblox')
            ->assertRedirect('http://www.sabex.lab/auth/roblox');
    }
}
