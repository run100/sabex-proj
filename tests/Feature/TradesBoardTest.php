<?php

namespace Tests\Feature;

use App\Models\SeoItem;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Services\Trades\TradeListingService;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradesBoardTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sab.hosts.www' => 'www.sabex.lab',
            'sab-trades.fair_threshold_percent' => 5,
        ]);
        $this->createSeoTables();
        $this->createTradeTables();
        $this->seedItem('noobini', 'Noobini', 10);
        $this->seedItem('cappuccino', 'Cappuccino', 12);
    }

    public function test_logged_in_trade_user_can_publish_listing(): void
    {
        $user = $this->tradeUser('12345', 'Trader');

        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [['slug' => 'noobini']],
                'looking_for' => [['slug' => 'cappuccino']],
                'note' => 'Looking for a fair swap',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $listing = TradeListing::query()->first();
        $this->assertNotNull($listing);
        $this->assertSame('win', $listing->result_snapshot);
        $this->assertSame(10.0, (float) $listing->offering_value_snapshot);
        $this->assertSame(12.0, (float) $listing->looking_value_snapshot);

        $home = $this->get('http://www.sabex.lab/trading')
            ->assertOk()
            ->assertSee('Noobini')
            ->assertSee('Cappuccino')
            ->assertSee('Trader')
            ->assertSee('Offering')
            ->assertSee('Looking for')
            ->assertSee('Open');
        $html = $home->getContent();
        $empty = substr_count($html, 'trades-item-slot--empty');
        $filled = substr_count($html, 'trades-item-slot--filled');
        $this->assertSame(16, $empty);
        $this->assertSame(2, $filled);
        $this->assertSame(18, $empty + $filled);

        $this->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee($listing->public_id);
    }

    public function test_guest_cannot_publish(): void
    {
        $this->postJson('http://www.sabex.lab/api/v1/trading/trades', [
            'offering' => [['slug' => 'noobini']],
            'looking_for' => [['slug' => 'cappuccino']],
        ])->assertUnauthorized()->assertJsonPath('error.code', 'AUTH_REQUIRED');
    }

    public function test_service_requires_both_sides(): void
    {
        $user = $this->tradeUser('9', 'x');
        $this->expectException(\App\Exceptions\TradeException::class);
        app(TradeListingService::class)->create($user, [], [['slug' => 'noobini']]);
    }

    public function test_join_accept_and_double_confirm_completes(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        $buyer = $this->tradeUser('2', 'Buyer');
        $other = $this->tradeUser('3', 'Other');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'CANNOT_JOIN_OWN_TRADE');

        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join', ['note' => 'I can do this'])
            ->assertCreated();
        $this->actingAs($other, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertCreated();

        $requests = $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join-requests')
            ->assertOk()
            ->json('data.items');
        $this->assertCount(2, $requests);

        $buyerJoin = collect($requests)->firstWhere('requester.username', 'buyer');
        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/join-requests/'.$buyerJoin['public_id'].'/accept')
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending');

        $listing->refresh();
        $this->assertSame('pending', $listing->status);
        $this->assertSame($buyer->id, $listing->counterparty_user_id);

        $this->actingAs($other, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join-requests')
            ->assertForbidden();

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending_confirmation');

        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'completed');

        $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/trading/completed')
            ->assertOk()
            ->assertSee('Noobini');
    }

    public function test_confirmation_conflict_is_disputed(): void
    {
        $owner = $this->tradeUser('11', 'Owner');
        $buyer = $this->tradeUser('12', 'Buyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertCreated();
        $joinId = $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join-requests')
            ->json('data.items.0.public_id');
        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/join-requests/'.$joinId.'/accept')
            ->assertOk();
        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed']);
        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'failed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'disputed');
    }

    public function test_expire_command_closes_open_listings(): void
    {
        $owner = $this->tradeUser('31', 'Owner');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $listing->expires_at = now()->subMinute();
        $listing->save();

        $this->artisan('seo:trade-expire')->assertSuccessful();
        $this->assertSame('expired', $listing->fresh()->status);
    }

    public function test_home_filters_listings_by_want_and_have_brainrot(): void
    {
        $alice = $this->tradeUser('41', 'Alice');
        $bob = $this->tradeUser('42', 'Bob');
        app(TradeListingService::class)->create($alice, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        app(TradeListingService::class)->create($bob, [['slug' => 'cappuccino']], [['slug' => 'noobini']]);

        $noobini = SeoItem::query()->where('slug', 'noobini')->firstOrFail();
        $cappuccino = SeoItem::query()->where('slug', 'cappuccino')->firstOrFail();

        $this->get('http://www.sabex.lab/trading?have_brainrot_id='.$noobini->id)
            ->assertOk()
            ->assertSee('Alice')
            ->assertDontSee('Bob')
            ->assertSee('value="'.$noobini->id.'"', false)
            ->assertSee('Noobini')
            ->assertSee('trades-filter__panel is-open', false);

        $this->get('http://www.sabex.lab/trading?want_brainrot_id='.$noobini->id)
            ->assertOk()
            ->assertSee('Bob')
            ->assertDontSee('Alice');

        $this->get('http://www.sabex.lab/trading?have_brainrot_id='.$cappuccino->id)
            ->assertOk()
            ->assertSee('Bob')
            ->assertDontSee('Alice');

        $this->get('http://www.sabex.lab/trading?want_brainrot_id=999999')
            ->assertOk()
            ->assertSee('Alice')
            ->assertSee('Bob')
            ->assertDontSee('trades-filter__panel is-open', false);
    }

    public function test_activity_page_copy_requires_login(): void
    {
        $this->get('http://www.sabex.lab/user/activity')
            ->assertRedirect('/auth/roblox?return_to='.urlencode('/user/activity'));
        $this->get('http://www.sabex.lab/trading/completed')
            ->assertOk()
            ->assertSee('Completed Steal a Brainrot Trades');

        $user = $this->tradeUser('51', 'Watcher');
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/activity')
            ->assertOk()
            ->assertSee('<title>SABExistCount - Steal a Brainrot Trade Activity &amp; Recent Trades</title>', false)
            ->assertSee('Track recent Steal a Brainrot trade activity on SABExistCount', false)
            ->assertSee('<h1>Steal a Brainrot Trade Activity</h1>', false)
            ->assertSee('Browse recent Steal a Brainrot trading activity, including newly posted', false)
            ->assertDontSee('<h1 class="mb-4 text-2xl font-black">Activity</h1>', false)
            ->assertDontSee('<title>Trade activity</title>', false)
            ->assertSee('href="/user/activity?status=all"', false)
            ->assertSee('href="/user/activity?status=pending"', false)
            ->assertSee('href="/user/activity?status=completed"', false)
            ->assertSee('href="/user/activity?status=failed"', false)
            ->assertDontSee('href="/user/activity?status=open"', false)
            ->assertDontSee('href="/user/activity?status=disputed"', false);
    }

    public function test_activity_tabs_count_open_listing_only_in_all(): void
    {
        $user = $this->tradeUser('52', 'Poster');
        $listing = app(TradeListingService::class)->create($user, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/activity')
            ->assertOk()
            ->assertSee('>All <span class="trades-activity-tabs__count">1</span>', false)
            ->assertSee('>Pending <span class="trades-activity-tabs__count">0</span>', false)
            ->assertSee('>Completed <span class="trades-activity-tabs__count">0</span>', false)
            ->assertSee('>Failed <span class="trades-activity-tabs__count">0</span>', false)
            ->assertSee($listing->public_id)
            ->assertDontSee('href="/user/activity?status=open"', false)
            ->assertDontSee('href="/user/activity?status=disputed"', false);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/activity?status=pending')
            ->assertOk()
            ->assertSee('>All <span class="trades-activity-tabs__count">1</span>', false)
            ->assertSee('>Pending <span class="trades-activity-tabs__count">0</span>', false)
            ->assertDontSee($listing->public_id)
            ->assertSee('No trades in this tab.');
    }

    public function test_home_paginates_newest_first_and_keeps_filters(): void
    {
        $this->raiseTradePostLimits();
        $user = $this->tradeUser('61', 'Pager');
        [$oldest, $newest] = $this->createNumberedListings($user, 21);
        $noobini = SeoItem::query()->where('slug', 'noobini')->firstOrFail();

        $this->get('http://www.sabex.lab/trading?have_brainrot_id='.$noobini->id)
            ->assertOk()
            ->assertSee($newest->public_id)
            ->assertDontSee($oldest->public_id)
            ->assertSee('page=2', false)
            ->assertSee('have_brainrot_id='.$noobini->id, false)
            ->assertSee('trades-pagination', false);

        $this->get('http://www.sabex.lab/trading?have_brainrot_id='.$noobini->id.'&page=2')
            ->assertOk()
            ->assertSee($oldest->public_id)
            ->assertDontSee($newest->public_id)
            ->assertSee('have_brainrot_id='.$noobini->id, false);
    }

    public function test_completed_paginates_newest_first(): void
    {
        $this->raiseTradePostLimits();
        $user = $this->tradeUser('62', 'Closer');
        [$oldest, $newest] = $this->createNumberedListings($user, 21, TradeListing::STATUS_COMPLETED);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/trading/completed')
            ->assertOk()
            ->assertSee($newest->public_id)
            ->assertDontSee($oldest->public_id)
            ->assertSee('page=2', false)
            ->assertSee('trades-pagination', false);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/trading/completed?page=2')
            ->assertOk()
            ->assertSee($oldest->public_id)
            ->assertDontSee($newest->public_id);
    }

    public function test_activity_paginates_newest_first_and_keeps_status(): void
    {
        $this->raiseTradePostLimits();
        $user = $this->tradeUser('63', 'Active');
        [$oldest, $newest] = $this->createNumberedListings($user, 21);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/activity?status=all')
            ->assertOk()
            ->assertSee($newest->public_id)
            ->assertDontSee($oldest->public_id)
            ->assertSee('page=2', false)
            ->assertSee('status=all', false)
            ->assertSee('trades-pagination', false);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/activity?status=all&page=2')
            ->assertOk()
            ->assertSee($oldest->public_id)
            ->assertDontSee($newest->public_id)
            ->assertSee('status=all', false);
    }

    public function test_block_prevents_join(): void
    {
        $owner = $this->tradeUser('21', 'Owner');
        $buyer = $this->tradeUser('22', 'Buyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/users/'.$buyer->profile_id.'/block')
            ->assertOk();
        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'USER_BLOCKED');
    }

    private function raiseTradePostLimits(): void
    {
        config([
            'sab-trades.max_active_listings_per_user' => 50,
            'sab-trades.post_trade_limit_per_hour' => 50,
        ]);
    }

    /**
     * @return array{0: TradeListing, 1: TradeListing}
     */
    private function createNumberedListings(TradeUser $user, int $count, ?string $status = null): array
    {
        $service = app(TradeListingService::class);
        $oldest = null;
        $newest = null;
        for ($i = 1; $i <= $count; $i++) {
            $listing = $service->create($user, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
            $listing->created_at = now()->subMinutes($count - $i + 1);
            if ($status !== null) {
                $listing->status = $status;
            }
            $listing->save();
            $oldest ??= $listing;
            $newest = $listing;
        }

        return [$oldest, $newest];
    }

    private function tradeUser(string $sub, string $name): TradeUser
    {
        return TradeUser::query()->create([
            'roblox_sub' => $sub,
            'username' => strtolower($name),
            'display_name' => $name,
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'last_login_at' => now(),
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
