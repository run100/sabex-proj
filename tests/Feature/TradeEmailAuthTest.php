<?php

namespace Tests\Feature;

use App\Mail\TradeVerifyEmail;
use App\Models\SeoGame;
use App\Models\SeoSite;
use App\Models\TradeAuthAccount;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use App\Services\Trades\EmailVerificationService;
use App\Services\Trades\TradeAuthAccountService;
use App\Support\TradeEmailAuth;
use App\Support\TradePresenter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradeEmailAuthTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function defineEnvironment($app): void
    {
        $app['env'] = 'local';
        $app['config']->set('app.env', 'local');
        $app['config']->set('sab-trades.allow_email_login', true);
        $app['config']->set('sab-trades.allow_email_bind', false);
        $app['config']->set('sab.hosts.www', 'www.sabex.lab');
        $app['config']->set('app.url', 'http://www.sabex.lab');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        config([
            'app.url' => 'http://www.sabex.lab',
            'sab.hosts.www' => 'www.sabex.lab',
            'sab-trades.allow_email_login' => true,
            'sab-trades.allow_email_bind' => false,
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
        Mail::fake();
    }

    public function test_register_sends_link_and_does_not_login(): void
    {
        $this->get('http://www.sabex.lab/auth/roblox')
            ->assertOk()
            ->assertSee('Email login')
            ->assertSee('Create an account');

        $this->get('http://www.sabex.lab/auth/register')
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('Confirm password')
            ->assertSee('Create a SAB Trades account');

        $response = $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'Ada_1',
            'email' => 'Ada@Example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/auth/email/check', $location);
        $this->assertStringNotContainsString('trades.sabex.lab', $location);
        $response->assertSessionHas('trade_status', TradeEmailAuth::VERIFY_PROMPT);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Check your email')
            ->assertSee(TradeEmailAuth::VERIFY_PROMPT)
            ->assertSee('color:#67e8f9');

        Mail::assertSent(TradeVerifyEmail::class, function (TradeVerifyEmail $mail): bool {
            return $mail->hasTo('ada@example.com');
        });

        $user = TradeUser::query()->where('email', 'ada@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('ada_1', $user->username);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->roblox_sub);
        $this->assertNull($user->roblox_user_id);
        $this->assertFalse($user->posting_approved);
        $this->assertTrue($user->hasProvider(TradeAuthAccount::PROVIDER_EMAIL));
        $this->assertGuest('trades');
    }

    public function test_verify_link_logs_in_and_password_works(): void
    {
        $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'ada',
            'email' => 'ada@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $user = TradeUser::query()->first();
        $this->get($this->verifyUrl($user, 'ada@example.com'))
            ->assertRedirect('/trading');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->posting_approved);
        $this->assertNotNull($user->posting_approved_at);
        $this->assertAuthenticatedAs($user, 'trades');

        $this->get('http://www.sabex.lab/trading/new')
            ->assertOk()
            ->assertSee('Publish Trade Ad')
            ->assertSee('data-publish-pending hidden', false);

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.can_post', true);

        $this->post('http://www.sabex.lab/logout')->assertRedirect('/trading');
        $this->assertGuest('trades');

        $this->post('http://www.sabex.lab/auth/email', [
            'email' => 'Ada@Example.com',
            'password' => 'password1',
        ])->assertRedirect('/trading');

        $this->assertAuthenticatedAs($user->fresh(), 'trades');

        $this->get('http://www.sabex.lab/profile/'.$user->profile_id)
            ->assertOk()
            ->assertSee($user->username)
            ->assertDontSee('View on Roblox');

        $this->get('http://www.sabex.lab/user')
            ->assertOk()
            ->assertSee('ada@example.com')
            ->assertDontSee('Send verification link')
            ->assertDontSee('Connect Roblox');
    }

    public function test_unverified_user_cannot_login(): void
    {
        $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'ada',
            'email' => 'ada@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $login = $this->post('http://www.sabex.lab/auth/email', [
            'email' => 'ada@example.com',
            'password' => 'password1',
        ]);
        $this->assertStringContainsString('/auth/email/check', (string) $login->headers->get('Location'));
        $this->assertGuest('trades');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'ada',
            'email' => 'ada@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);
        $user = TradeUser::query()->first();
        $this->get($this->verifyUrl($user, 'ada@example.com'));
        $this->post('http://www.sabex.lab/logout');

        $this->post('http://www.sabex.lab/auth/email', [
            'email' => 'ada@example.com',
            'password' => 'wrong-pass',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('trades');
    }

    public function test_historical_bind_verify_still_redirects_to_user(): void
    {
        $user = TradeUser::query()->create([
            'roblox_sub' => '12345',
            'roblox_user_id' => 12345,
            'username' => 'trader',
            'display_name' => 'Trader',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        app(TradeAuthAccountService::class)->attachIdentity($user, TradeAuthAccount::PROVIDER_ROBLOX, '12345', [
            'username' => 'trader',
        ]);
        Cache::put('trades.email.bind:'.$user->id, [
            'email' => 'trader@example.com',
            'password' => 'password1',
            'intent' => EmailVerificationService::INTENT_BIND,
        ], now()->addHour());

        $this->actingAs($user, 'trades')
            ->get($this->verifyUrl($user, 'trader@example.com'))
            ->assertRedirect('/user');

        $user->refresh();
        $this->assertSame('trader@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasProvider(TradeAuthAccount::PROVIDER_EMAIL));
    }

    public function test_password_confirmation_mismatch_returns_to_register(): void
    {
        $this->from('http://www.sabex.lab/auth/register')
            ->post('http://www.sabex.lab/auth/register', [
                'username' => 'ada',
                'email' => 'ada@example.com',
                'password' => 'password1',
                'password_confirmation' => 'password2',
            ])
            ->assertRedirect('http://www.sabex.lab/auth/register')
            ->assertSessionHasErrors('password');

        $this->assertSame(0, TradeUser::query()->count());
        $this->assertGuest('trades');
    }

    public function test_unverified_email_resubmit_goes_to_check_page(): void
    {
        $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'ada',
            'email' => 'ada@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $this->from('http://www.sabex.lab/auth/register')
            ->post('http://www.sabex.lab/auth/register', [
                'username' => 'ada_2',
                'email' => 'ada@example.com',
                'password' => 'password1',
                'password_confirmation' => 'password1',
            ])
            ->assertRedirect('http://www.sabex.lab/auth/email/check')
            ->assertSessionHas('trade_status', TradeEmailAuth::VERIFY_PROMPT)
            ->assertSessionMissing('errors');

        $this->assertSame(1, TradeUser::query()->count());
        $this->assertGuest('trades');
    }

    public function test_verified_email_stays_already_registered(): void
    {
        $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'ada',
            'email' => 'ada@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);
        $user = TradeUser::query()->first();
        $this->get($this->verifyUrl($user, 'ada@example.com'));
        $this->post('http://www.sabex.lab/logout');

        $this->from('http://www.sabex.lab/auth/register')
            ->post('http://www.sabex.lab/auth/register', [
                'username' => 'ada_2',
                'email' => 'ada@example.com',
                'password' => 'password1',
                'password_confirmation' => 'password1',
            ])
            ->assertRedirect('http://www.sabex.lab/auth/register')
            ->assertSessionHasErrors(['email' => TradeEmailAuth::EMAIL_ALREADY_REGISTERED]);

        $html = view('trades.register', TradePresenter::page([
            'seoTitle' => 'Create a SAB Trades account',
            'seoDescription' => 'Register with a username, email, and password.',
            'canonical' => 'http://www.sabex.lab/auth/register',
            'robots' => 'noindex,nofollow',
        ]))->withErrors(['email' => TradeEmailAuth::EMAIL_ALREADY_REGISTERED])->render();

        $this->assertStringContainsString(TradeEmailAuth::EMAIL_ALREADY_REGISTERED, $html);
        $this->assertStringContainsString('border-amber-700/50', $html);
        $this->assertStringContainsString('text-amber-100', $html);
        $this->assertSame(1, TradeUser::query()->count());
    }

    public function test_header_account_chip_for_email_user_without_avatar(): void
    {
        $user = TradeUser::query()->create([
            'username' => 'cool',
            'display_name' => 'cool',
            'email' => 'cool@example.com',
            'email_verified_at' => now(),
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'password' => 'password1',
            'roblox_sub' => null,
        ]);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/auth/email')
            ->assertOk()
            ->assertSee('/static/js/sab-nav-auth.js', false)
            ->assertSee('data-nav-auth', false)
            ->assertSee('data-nav-sign-in', false)
            ->assertDontSee('>cool</span>', false)
            ->assertDontSee('trades-header-account', false);
    }

    public function test_header_shows_sign_in_when_guest(): void
    {
        $this->get('http://www.sabex.lab/auth/email')
            ->assertOk()
            ->assertSee('/static/js/sab-nav-auth.js', false)
            ->assertSee('data-nav-sign-in', false)
            ->assertSee('>Login</a>', false)
            ->assertDontSee('trades-header-account', false);
    }

    public function test_local_register_writes_local_identity(): void
    {
        $this->post('http://www.sabex.lab/auth/local/register', [
            'username' => 'alice_1',
            'password' => 'password1',
        ])->assertRedirect('/trading');

        $user = TradeUser::query()->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasProvider(TradeAuthAccount::PROVIDER_LOCAL));
        $this->assertSame('local:alice_1', $user->identities()->first()->provider_uid);
        $this->assertNull($user->roblox_user_id);
    }

    private function verifyUrl(TradeUser $user, string $email): string
    {
        $this->get('http://www.sabex.lab/auth/email');

        return app(EmailVerificationService::class)->signedUrl($user, $email);
    }
}
