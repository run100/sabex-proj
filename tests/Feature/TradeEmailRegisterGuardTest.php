<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoSite;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradeEmailRegisterGuardTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function defineEnvironment($app): void
    {
        $app['env'] = 'production';
        $app['config']->set('app.env', 'production');
        $app['config']->set('sab-trades.allow_email_login', true);
        $app['config']->set('sab-trades.allow_email_bind', false);
        $app['config']->set('sab.hosts.www', 'www.sabex.lab');
        $app['config']->set('app.url', 'http://www.sabex.lab');
    }

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_production_does_not_expose_register_even_when_email_login_is_on(): void
    {
        $this->get('http://www.sabex.lab/auth/register')->assertNotFound();
        $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'ada',
            'email' => 'ada@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertNotFound();
        $this->assertSame(0, TradeUser::query()->count());

        $this->get('http://www.sabex.lab/auth/email')
            ->assertOk()
            ->assertSee('Email login')
            ->assertDontSee('Create an account');

        $this->get('http://www.sabex.lab/auth/roblox')
            ->assertOk()
            ->assertSee('Email login')
            ->assertDontSee('Create an account');
    }
}
