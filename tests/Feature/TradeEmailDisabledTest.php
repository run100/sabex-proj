<?php

namespace Tests\Feature;

use App\Mail\TradeVerifyEmail;
use App\Models\TradeUser;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradeEmailDisabledTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sab-trades.allow_email_login', false);
        $app['config']->set('sab-trades.allow_email_bind', false);
        $app['config']->set('sab.hosts.www', 'www.sabex.lab');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeoTables();
        $this->createTradeTables();
        Mail::fake();
    }

    public function test_email_login_routes_are_404_and_verify_stays_registered(): void
    {
        $this->get('http://www.sabex.lab/auth/email')->assertNotFound();
        $this->get('http://www.sabex.lab/auth/register')->assertNotFound();
        $this->post('http://www.sabex.lab/auth/register', [
            'username' => 'ada',
            'email' => 'ada@example.com',
            'password' => 'password1',
        ])->assertNotFound();
        $this->post('http://www.sabex.lab/auth/email/resend', [
            'email' => 'ada@example.com',
        ])->assertNotFound();
        Mail::assertNothingSent();
        $this->assertSame(0, TradeUser::query()->count());

        $user = TradeUser::query()->create([
            'username' => 'ada',
            'email' => 'ada@example.com',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $url = app(\App\Services\Trades\EmailVerificationService::class)->signedUrl($user, 'ada@example.com');
        $this->get($url)->assertRedirect('/trading');
        Mail::assertNotSent(TradeVerifyEmail::class);
    }
}
