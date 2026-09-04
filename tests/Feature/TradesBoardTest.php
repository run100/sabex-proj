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
            'sab.hosts.trades' => 'trades.sabex.lab',
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
            ->postJson('http://trades.sabex.lab/api/v1/trades', [
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

        $this->get('http://trades.sabex.lab/')
            ->assertOk()
            ->assertSee('Noobini')
            ->assertSee('Cappuccino')
            ->assertSee('Trader');

        $this->get('http://trades.sabex.lab/t/'.$listing->public_id)
            ->assertOk()
            ->assertSee($listing->public_id);
    }

    public function test_guest_cannot_publish(): void
    {
        $this->postJson('http://trades.sabex.lab/api/v1/trades', [
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
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'CANNOT_JOIN_OWN_TRADE');

        $this->actingAs($buyer, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join', ['note' => 'I can do this'])
            ->assertCreated();
        $this->actingAs($other, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join')
            ->assertCreated();

        $requests = $this->actingAs($owner, 'trades')
            ->getJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join-requests')
            ->assertOk()
            ->json('data.items');
        $this->assertCount(2, $requests);

        $buyerJoin = collect($requests)->firstWhere('requester.username', 'buyer');
        $this->actingAs($owner, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/join-requests/'.$buyerJoin['public_id'].'/accept')
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending');

        $listing->refresh();
        $this->assertSame('pending', $listing->status);
        $this->assertSame($buyer->id, $listing->counterparty_user_id);

        $this->actingAs($other, 'trades')
            ->getJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join-requests')
            ->assertForbidden();

        $this->actingAs($owner, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending_confirmation');

        $this->actingAs($buyer, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'completed');

        $this->get('http://trades.sabex.lab/completed')
            ->assertOk()
            ->assertSee('Noobini');
    }

    public function test_confirmation_conflict_is_disputed(): void
    {
        $owner = $this->tradeUser('11', 'Owner');
        $buyer = $this->tradeUser('12', 'Buyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $this->actingAs($buyer, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join')
            ->assertCreated();
        $joinId = $this->actingAs($owner, 'trades')
            ->getJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join-requests')
            ->json('data.items.0.public_id');
        $this->actingAs($owner, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/join-requests/'.$joinId.'/accept')
            ->assertOk();
        $this->actingAs($owner, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed']);
        $this->actingAs($buyer, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'failed'])
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

    public function test_block_prevents_join(): void
    {
        $owner = $this->tradeUser('21', 'Owner');
        $buyer = $this->tradeUser('22', 'Buyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $this->actingAs($owner, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/users/'.$buyer->roblox_sub.'/block')
            ->assertOk();
        $this->actingAs($buyer, 'trades')
            ->postJson('http://trades.sabex.lab/api/v1/trades/'.$listing->public_id.'/join')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'USER_BLOCKED');
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
