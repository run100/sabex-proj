<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoSite;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use App\Services\Trades\TradeJoinService;
use App\Services\Trades\TradeListingService;
use App\Services\Trades\TradeModerationService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradeLaunchGuardTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sab.hosts.www' => 'www.sabex.lab',
            'sab-trades.moderator_roblox_subs' => ['999'],
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
        $this->seedItem('noobini', 'Noobini', 10);
        $this->seedItem('cappuccino', 'Cappuccino', 12);
    }

    public function test_api_uses_web_session_and_csrf(): void
    {
        $user = $this->tradeUser('1', 'Owner');
        $guestMe = $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user', null)
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.open_listings_count', 0);
        $this->assertStringContainsString('no-store', (string) $guestMe->headers->get('Cache-Control'));

        $this->actingAs($user, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'owner')
            ->assertJsonPath('data.user.can_post', true)
            ->assertJsonPath('data.open_listings_count', 0)
            ->assertJsonMissingPath('data.user.email')
            ->assertJsonMissingPath('data.user.providers')
            ->assertJsonMissingPath('data.user.roblox_sub');

        $request = Request::create('http://www.sabex.lab/api/v1/trading/trades', 'POST');
        $route = Route::getRoutes()->match($request);
        $middleware = collect($route->gatherMiddleware());
        $this->assertTrue(
            $middleware->contains('web')
            || $middleware->contains(PreventRequestForgery::class)
            || $middleware->contains(fn ($name) => is_string($name) && str_contains($name, 'PreventRequestForgery')),
            'Trading write API must run on the web middleware stack so CSRF applies outside tests.'
        );
    }

    public function test_non_moderator_hide_is_forbidden(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        $other = $this->tradeUser('2', 'Other');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/hide')
            ->assertForbidden();
        $this->actingAs($other, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/hide')
            ->assertForbidden();
        $this->actingAs($other, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/cancel')
            ->assertForbidden();
    }

    public function test_non_owner_cannot_accept_or_cancel_join(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        $buyer = $this->tradeUser('2', 'Buyer');
        $other = $this->tradeUser('3', 'Other');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $join = app(TradeJoinService::class)->join($buyer, $listing, null);

        $this->actingAs($other, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/join-requests/'.$join->public_id.'/accept')
            ->assertForbidden();
        $this->actingAs($other, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/join-requests/'.$join->public_id.'/cancel')
            ->assertForbidden();
    }

    public function test_non_moderator_cannot_review_report(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        $reporter = $this->tradeUser('2', 'Reporter');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $report = app(TradeModerationService::class)->report($reporter, [
            'listing_public_id' => $listing->public_id,
            'reason' => 'spam',
        ]);

        $this->actingAs($reporter, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/reports/'.$report->public_id.'/review', [
                'status' => 'resolved',
            ])
            ->assertNotFound();
    }

    public function test_private_user_appears_on_public_trade_without_profile_link(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        $owner->profile_visibility = TradeUser::VISIBILITY_PRIVATE;
        $owner->save();
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $home = $this->get('http://www.sabex.lab/trading')->assertOk();
        $home->assertSee('Owner');
        $home->assertDontSee($owner->profilePath());

        $json = $this->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id)
            ->assertOk()
            ->json('data.trade.owner');
        $this->assertSame('owner', $json['username']);
        $this->assertArrayNotHasKey('profile_path', $json);
        $this->assertArrayNotHasKey('profile_url', $json);
        $this->assertArrayNotHasKey('email', $json);
        $this->assertArrayNotHasKey('providers', $json);
        $this->assertArrayNotHasKey('roblox_sub', $json);

        $this->get('http://www.sabex.lab/profile/'.$owner->profile_id)->assertNotFound();
        $this->getJson('http://www.sabex.lab/api/v1/users/'.$owner->profile_id)->assertNotFound();
        $this->getJson('http://www.sabex.lab/api/v1/users/'.$owner->profile_id.'/trades')->assertNotFound();
    }

    public function test_banned_user_trades_are_hidden(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $owner->account_status = TradeUser::STATUS_BANNED;
        $owner->save();

        $this->get('http://www.sabex.lab/trading')->assertOk()->assertDontSee($listing->public_id);
        $this->get('http://www.sabex.lab/trading/'.$listing->public_id)->assertNotFound();
        $this->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id)->assertNotFound();
    }

    public function test_pending_is_public_and_new_is_indexable(): void
    {
        $this->get('http://www.sabex.lab/trading/pending')
            ->assertOk()
            ->assertSee('Pending Steal a Brainrot Trades');
        $this->get('http://www.sabex.lab/trading/new')
            ->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('Post a Steal a Brainrot trade ad on SABExistCount. Add I Have and I Want items with mutations and traits, compare SAB values, income, exist counts, and W/F/L, then publish. Finish the swap in Roblox.', false)
            ->assertSee('mutations and traits')
            ->assertSee('W/F/L')
            ->assertSee('trades-show__back', false)
            ->assertSee('All Trades')
            ->assertSee('Create Trade Ad')
            ->assertSee('I Have')
            ->assertSee('I Want')
            ->assertSee('Publish Trade Ad')
            ->assertSee('Under development.')
            ->assertDontSee('Continue with Roblox')
            ->assertDontSee('Email login')
            ->assertDontSee('Create an account')
            ->assertDontSee('<section class="sab-calc-content', false)
            ->assertDontSee('id="faq"', false);

        $this->get('http://www.sabex.lab/sitemaps/main.xml')
            ->assertOk()
            ->assertSee('https://sabexistcount.com/trading/new');
    }

    public function test_trade_create_exposes_listing_note_and_builder_payload(): void
    {
        $this->get('http://www.sabex.lab/trading/new')
            ->assertOk()
            ->assertSee('Add a note')
            ->assertSee('trade-note', false)
            ->assertSee('data-trade-note', false)
            ->assertSee('data-note-count', false);

        $builder = file_get_contents(public_path('static/js/sab-trade-builder.js'));
        $this->assertIsString($builder);
        $this->assertStringContainsString('noteInput', $builder);
        $this->assertStringContainsString('noteCount', $builder);
        $this->assertStringContainsString('data-trade-note', $builder);
        $this->assertStringContainsString('draft.note', $builder);
        $this->assertStringContainsString('note,', $builder);
    }

    public function test_hidden_listing_is_404(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $listing->status = TradeListing::STATUS_HIDDEN;
        $listing->save();

        $this->get('http://www.sabex.lab/trading/'.$listing->public_id)->assertNotFound();
    }

    public function test_invalid_page_is_404(): void
    {
        $this->get('http://www.sabex.lab/trading?page=0')->assertNotFound();
        $this->get('http://www.sabex.lab/trading?page=2')->assertNotFound();
    }

    public function test_web_trade_query_arrays_are_rejected_before_casts(): void
    {
        foreach ([
            'http://www.sabex.lab/trading?page[]=1',
            'http://www.sabex.lab/trading?sort[]=newest',
            'http://www.sabex.lab/trading/completed?username[]=owner',
            'http://www.sabex.lab/trading/pending?limit[]=20',
        ] as $url) {
            $this->get($url)->assertNotFound();
        }

        $user = $this->tradeUser('4', 'InputUser');
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers?status[]=sent')
            ->assertNotFound();
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/notifications?page[]=1')
            ->assertNotFound();
    }

    private function tradeUser(string $sub, string $name): TradeUser
    {
        return TradeUser::query()->create([
            'roblox_sub' => $sub,
            'roblox_user_id' => ctype_digit($sub) ? $sub : null,
            'username' => strtolower($name),
            'display_name' => $name,
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
    }

    private function seedItem(string $slug, string $name, float $value): void
    {
        SeoItem::query()->create([
            'seo_game_id' => 1,
            'slug' => $slug,
            'name' => $name,
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
                    'name' => $name,
                    'base_income' => 10,
                    'robux_value' => $value,
                    'demand' => 'HIGH',
                ],
            ],
            'sort_order' => 0,
        ]);
    }
}
