<?php

namespace Tests\Feature;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoSite;
use App\Models\SeoUser;
use App\Models\TradeEmailCode;
use App\Models\TradeEvent;
use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Models\TradeListingItemTrait;
use App\Models\TradeNotification;
use App\Models\TradeReport;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class TradePostingApprovalTest extends TestCase
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
        $this->seedItem('noobini', 'Noobini', 10);
        $this->seedItem('cappuccino', 'Cappuccino', 12);
    }

    public function test_unapproved_local_user_cannot_publish(): void
    {
        $this->post('http://www.sabex.lab/auth/local/register', [
            'username' => 'alice',
            'password' => 'password1',
        ])->assertRedirect('/trading');

        $user = TradeUser::query()->first();
        $this->assertFalse($user->posting_approved);
        $this->assertNull($user->posting_approved_at);

        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $this->tradePayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'POSTING_NOT_APPROVED');

        $this->assertSame(0, TradeListing::query()->count());
    }

    public function test_admin_can_approve_then_revoke_posting(): void
    {
        $this->post('http://www.sabex.lab/auth/local/register', [
            'username' => 'bob',
            'password' => 'password1',
        ])->assertRedirect('/trading');

        $tradeUser = TradeUser::query()->first();
        $admin = SeoUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-users')
            ->assertOk()
            ->assertJsonPath('users.0.username', 'bob')
            ->assertJsonPath('users.0.posting_approved', false);

        $approved = $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-users/'.$tradeUser->id, [
                'posting_approved' => true,
            ])
            ->assertOk()
            ->assertJsonPath('user.posting_approved', true);
        $this->assertNotEmpty($approved->json('user.posting_approved_at'));

        $tradeUser->refresh();
        $this->assertTrue($tradeUser->posting_approved);
        $this->assertNotNull($tradeUser->posting_approved_at);
        $approvedAt = $tradeUser->posting_approved_at;

        $this->actingAs($tradeUser, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $this->tradePayload())
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-users/'.$tradeUser->id, [
                'posting_approved' => false,
            ])
            ->assertOk()
            ->assertJsonPath('user.posting_approved', false)
            ->assertJsonPath('user.posting_approved_at', null);

        $tradeUser->refresh();
        $this->assertFalse($tradeUser->posting_approved);
        $this->assertNull($tradeUser->posting_approved_at);
        $this->assertNotEquals($approvedAt, $tradeUser->posting_approved_at);

        $this->actingAs($tradeUser, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $this->tradePayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'POSTING_NOT_APPROVED');
    }

    public function test_admin_trade_users_include_email_and_search(): void
    {
        $admin = SeoUser::factory()->create();
        TradeUser::query()->create([
            'username' => 'mailer',
            'display_name' => 'Mailer',
            'email' => 'mailer@example.com',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'posting_approved' => false,
        ]);

        $list = $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-users')
            ->assertOk()
            ->assertJsonPath('users.0.email', 'mailer@example.com')
            ->assertJsonPath('users.0.profile_visibility', TradeUser::VISIBILITY_PUBLIC)
            ->assertJsonPath('users.0.moderation_status', TradeUser::MODERATION_CLEAR)
            ->assertJsonPath('users.0.profile_index_eligible', false);
        $this->assertContains('email', $list->json('users.0.providers'));
        $this->assertNotEmpty($list->json('users.0.profile_id'));

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-users?q=mailer@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.username', 'mailer');

        $userId = $list->json('users.0.id');
        auth('admin')->logout();
        $this->getJson('http://x.sabex.lab/api/trade-users/'.$userId)
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-users/'.$userId)
            ->assertOk()
            ->assertJsonPath('user.username', 'mailer')
            ->assertJsonPath('user.email', 'mailer@example.com')
            ->assertJsonPath('user.profile_id', $list->json('users.0.profile_id'))
            ->assertJsonPath('user.avatar_url', $list->json('users.0.avatar_url'))
            ->assertJsonPath('user.profile_url', $list->json('users.0.profile_url'));
    }

    public function test_admin_can_search_and_patch_trade_user_profile_fields(): void
    {
        $admin = SeoUser::factory()->create();
        $user = TradeUser::query()->create([
            'username' => 'patcheable',
            'display_name' => 'Patcheable',
            'email' => 'patch@example.com',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'posting_approved' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-users?q='.$user->profile_id)
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.profile_id', $user->profile_id);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-users/'.$user->id, [
                'account_status' => TradeUser::STATUS_SUSPENDED,
                'profile_visibility' => TradeUser::VISIBILITY_PRIVATE,
                'moderation_status' => TradeUser::MODERATION_REVIEW,
                'profile_index_eligible' => true,
            ])
            ->assertOk()
            ->assertJsonPath('user.account_status', TradeUser::STATUS_SUSPENDED)
            ->assertJsonPath('user.profile_visibility', TradeUser::VISIBILITY_PRIVATE)
            ->assertJsonPath('user.moderation_status', TradeUser::MODERATION_REVIEW)
            ->assertJsonPath('user.profile_index_eligible', true);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-users/'.$user->id, [
                'account_status' => TradeUser::STATUS_DELETED,
            ])
            ->assertStatus(422);

        $user->refresh();
        $this->assertSame(TradeUser::STATUS_SUSPENDED, $user->account_status);
        $this->assertSame(TradeUser::VISIBILITY_PRIVATE, $user->profile_visibility);
        $this->assertSame(TradeUser::MODERATION_REVIEW, $user->moderation_status);
        $this->assertTrue($user->profile_index_eligible);
    }

    public function test_admin_trade_listings_empty_then_readable(): void
    {
        $admin = SeoUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-listings')
            ->assertOk()
            ->assertJsonPath('listings', [])
            ->assertJsonPath('counts.all', 0)
            ->assertJsonPath('counts.open', 0);

        $owner = TradeUser::query()->create([
            'username' => 'owner',
            'display_name' => 'Owner',
            'email' => 'owner@example.com',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $listing = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_OPEN,
            'note' => 'admin list',
            'offering_value_snapshot' => 10,
            'looking_value_snapshot' => 12,
        ]);
        $catalog = SeoItem::query()->where('slug', 'noobini')->first();
        $item = TradeListingItem::query()->create([
            'listing_id' => $listing->id,
            'side' => TradeListingItem::SIDE_OFFERING,
            'slot_no' => 1,
            'seo_item_id' => $catalog->id,
            'slug_snapshot' => 'noobini',
            'brainrot_name_snapshot' => 'Noobini',
            'final_value_snapshot' => 10,
        ]);
        TradeListingItemTrait::query()->create([
            'listing_item_id' => $item->id,
            'trait_name' => 'Cursed',
            'trait_name_snapshot' => 'Cursed',
            'value_multiplier_snapshot' => 1.5,
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-listings')
            ->assertOk()
            ->assertJsonPath('listings.0.public_id', $listing->public_id)
            ->assertJsonPath('listings.0.status', 'open')
            ->assertJsonPath('listings.0.owner_email', 'owner@example.com')
            ->assertJsonPath('listings.0.owner_profile_id', $owner->profile_id)
            ->assertJsonPath('listings.0.owner_avatar_url', $owner->avatar_url)
            ->assertJsonPath('listings.0.sort_order', 0)
            ->assertJsonPath('listings.0.is_hot', 'N')
            ->assertJsonPath('listings.0.is_top', 'N')
            ->assertJsonPath('counts.all', 1)
            ->assertJsonPath('counts.open', 1);

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-listings?q=owner@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'listings');

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-listings/'.$listing->id)
            ->assertOk()
            ->assertJsonPath('listing.public_id', $listing->public_id)
            ->assertJsonPath('listing.owner_id', $owner->id)
            ->assertJsonPath('listing.owner_username', 'owner')
            ->assertJsonPath('listing.owner_email', 'owner@example.com')
            ->assertJsonPath('listing.owner_profile_id', $owner->profile_id)
            ->assertJsonPath('listing.items.0.slug', 'noobini')
            ->assertJsonPath('listing.items.0.traits.0.name', 'Cursed')
            ->assertJsonPath('listing.join_requests', [])
            ->assertJsonPath('listing.events', [])
            ->assertJsonPath('listing.reports', []);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$listing->id, [
                'status' => 'hidden',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'hidden');

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$listing->id, [
                'status' => 'open',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'open');

        $updated = $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$listing->id, [
                'result_snapshot' => 'fair',
                'note' => 'admin note',
                'views_count' => 12,
                'expires_at' => '2026-12-01 08:00:00',
            ])
            ->assertOk()
            ->assertJsonPath('listing.result_snapshot', 'fair')
            ->assertJsonPath('listing.note', 'admin note')
            ->assertJsonPath('listing.views_count', 12);
        $this->assertNotEmpty($updated->json('listing.expires_at'));

        foreach (['<script>alert(1)</script>', 'https://sabexistcount.com', 'a/b', ['blocked']] as $note) {
            $this->actingAs($admin, 'admin')
                ->patchJson('http://x.sabex.lab/api/trade-listings/'.$listing->id, ['note' => $note])
                ->assertStatus(422);
            $this->assertSame('admin note', $listing->fresh()->note);
        }

        $sqlNote = '; DROP TABLE seo_trade_listings; --';
        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$listing->id, ['note' => $sqlNote])
            ->assertOk()
            ->assertJsonPath('listing.note', $sqlNote);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$listing->id, [
                'result_snapshot' => 'maybe',
            ])
            ->assertStatus(422);

        foreach ([
            'http://x.sabex.lab/api/trade-listings?q[]=owner',
            'http://x.sabex.lab/api/trade-listings?status[]=open',
            'http://x.sabex.lab/api/trade-joins?status[]=requested',
            'http://x.sabex.lab/api/trade-reports?status[]=open',
            'http://x.sabex.lab/api/trade-users?q[]=owner',
        ] as $url) {
            $this->actingAs($admin, 'admin')
                ->getJson($url)
                ->assertStatus(422);
        }
    }

    public function test_admin_can_unhide_or_force_close_hidden_listings(): void
    {
        $admin = SeoUser::factory()->create();
        $owner = TradeUser::query()->create([
            'username' => 'hidden-owner',
            'display_name' => 'Hidden Owner',
            'email' => 'hidden-owner@example.com',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $peer = TradeUser::query()->create([
            'username' => 'hidden-peer',
            'display_name' => 'Hidden Peer',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);

        $legacy = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_HIDDEN,
        ]);
        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$legacy->id, [
                'status' => 'unhidden',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'open');

        $pending = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'counterparty_user_id' => $peer->id,
            'status' => TradeListing::STATUS_PENDING,
        ]);
        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$pending->id, [
                'status' => 'hidden',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'hidden');
        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$pending->id, [
                'status' => 'unhidden',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'pending');

        $hidden = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_HIDDEN,
        ]);
        $closed = $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$hidden->id, [
                'status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'cancelled');
        $this->assertNotEmpty($closed->json('listing.cancelled_at'));

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$pending->id, [
                'status' => 'unhidden',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_force_close_open_and_pending_listings(): void
    {
        $admin = SeoUser::factory()->create();
        $owner = TradeUser::query()->create([
            'username' => 'closer',
            'display_name' => 'Closer',
            'email' => 'closer@example.com',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $peer = TradeUser::query()->create([
            'username' => 'peer',
            'display_name' => 'Peer',
            'email' => 'peer@example.com',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $open = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_OPEN,
        ]);
        $join = TradeJoinRequest::query()->create([
            'listing_id' => $open->id,
            'requester_user_id' => $peer->id,
            'owner_user_id' => $owner->id,
            'status' => TradeJoinRequest::STATUS_REQUESTED,
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-listings/'.$open->id)
            ->assertOk()
            ->assertJsonPath('listing.join_requests.0.id', $join->id)
            ->assertJsonPath('listing.join_requests.0.requester_id', $peer->id)
            ->assertJsonPath('listing.join_requests.0.requester_username', 'peer')
            ->assertJsonPath('listing.join_requests.0.status', 'requested');

        $closed = $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$open->id, [
                'status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'cancelled');
        $this->assertNotEmpty($closed->json('listing.cancelled_at'));
        $this->assertSame('cancelled', TradeJoinRequest::query()->first()->status);
        $this->assertNotNull(TradeJoinRequest::query()->first()->cancelled_at);
        $this->assertSame(1, TradeEvent::query()->where('event_type', 'trade_cancelled')->count());
        $this->assertSame(1, TradeNotification::query()->where('user_id', $owner->id)->count());

        $pending = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'counterparty_user_id' => $peer->id,
            'status' => TradeListing::STATUS_PENDING,
        ]);
        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$pending->id, [
                'status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('listing.status', 'cancelled');
        $this->assertSame(2, TradeNotification::query()->where('listing_id', $pending->id)->count());

        $done = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_COMPLETED,
        ]);
        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$done->id, [
                'status' => 'cancelled',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_pin_listing_and_market_sorts_top_first(): void
    {
        $admin = SeoUser::factory()->create();
        $owner = TradeUser::query()->create([
            'username' => 'pin-owner',
            'display_name' => 'Pin Owner',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);

        $older = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_OPEN,
            'created_at' => now()->subMinute(),
        ]);
        $newer = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_OPEN,
            'created_at' => now(),
        ]);

        $this->assertSame(0, (int) $older->sort_order);
        $this->assertSame(TradeListing::FLAG_NO, $older->is_hot);
        $this->assertSame(TradeListing::FLAG_NO, $older->is_top);

        $this->getJson('http://www.sabex.lab/api/v1/trading/trades')
            ->assertOk()
            ->assertJsonPath('data.items.0.public_id', $newer->public_id);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$older->id, [
                'is_top' => TradeListing::FLAG_YES,
                'sort_order' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('listing.is_top', 'Y')
            ->assertJsonPath('listing.is_hot', 'N')
            ->assertJsonPath('listing.sort_order', 10);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$older->id, [
                'is_hot' => TradeListing::FLAG_YES,
            ])
            ->assertOk()
            ->assertJsonPath('listing.is_hot', 'Y');

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$older->id, [
                'is_hot' => 'yes',
            ])
            ->assertStatus(422);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-listings/'.$older->id, [])
            ->assertStatus(422);

        $this->getJson('http://www.sabex.lab/api/v1/trading/trades')
            ->assertOk()
            ->assertJsonPath('data.items.0.public_id', $older->public_id)
            ->assertJsonPath('data.items.0.is_top', 'Y')
            ->assertJsonPath('data.items.1.public_id', $newer->public_id);

        $this->get('http://www.sabex.lab/trading')
            ->assertOk()
            ->assertSee('trades-card-board__flag--top', false)
            ->assertSee('>Top</span>', false)
            ->assertSee('trades-card-board__flag--hot', false)
            ->assertSee('>Hot</span>', false);

        $older->refresh();
        $this->assertSame(TradeListing::FLAG_YES, $older->is_top);
        $this->assertSame(TradeListing::FLAG_YES, $older->is_hot);
        $this->assertSame(10, (int) $older->sort_order);
    }

    public function test_admin_trade_joins_reports_and_masked_email_codes(): void
    {
        $admin = SeoUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-joins')
            ->assertOk()
            ->assertJsonPath('joins', []);

        $reporter = TradeUser::query()->create([
            'username' => 'reporter',
            'display_name' => 'Reporter',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $report = TradeReport::query()->create([
            'reporter_user_id' => $reporter->id,
            'reason' => 'spam',
            'status' => 'open',
        ]);

        $this->actingAs($admin, 'admin')
            ->patchJson('http://x.sabex.lab/api/trade-reports/'.$report->id, [
                'status' => 'resolved',
                'resolution_note' => 'checked',
            ])
            ->assertOk()
            ->assertJsonPath('report.status', 'resolved')
            ->assertJsonPath('report.resolution_note', 'checked')
            ->assertJsonPath('report.reporter_profile_id', $reporter->profile_id);

        TradeEmailCode::query()->create([
            'email' => 'code@example.com',
            'code' => '123456',
            'purpose' => TradeEmailCode::PURPOSE_LOGIN,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $codes = $this->actingAs($admin, 'admin')
            ->getJson('http://x.sabex.lab/api/trade-email-codes')
            ->assertOk()
            ->assertJsonPath('codes.0.email', 'code@example.com')
            ->assertJsonPath('codes.0.code', '******');
        $this->assertStringNotContainsString('123456', $codes->getContent());
    }

    public function test_admin_can_delete_requested_send_offer(): void
    {
        $admin = SeoUser::factory()->create();
        $owner = TradeUser::query()->create([
            'username' => 'offer-owner',
            'display_name' => 'Offer Owner',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $peer = TradeUser::query()->create([
            'username' => 'offer-peer',
            'display_name' => 'Offer Peer',
            'account_status' => TradeUser::STATUS_ACTIVE,
        ]);
        $listing = TradeListing::query()->create([
            'owner_user_id' => $owner->id,
            'status' => TradeListing::STATUS_OPEN,
        ]);
        $requested = TradeJoinRequest::query()->create([
            'listing_id' => $listing->id,
            'requester_user_id' => $peer->id,
            'owner_user_id' => $owner->id,
            'status' => TradeJoinRequest::STATUS_REQUESTED,
        ]);
        $accepted = TradeJoinRequest::query()->create([
            'listing_id' => $listing->id,
            'requester_user_id' => $peer->id,
            'owner_user_id' => $owner->id,
            'status' => TradeJoinRequest::STATUS_ACCEPTED,
        ]);

        $this->deleteJson('http://x.sabex.lab/api/trade-joins/'.$requested->id)
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->deleteJson('http://x.sabex.lab/api/trade-joins/'.$accepted->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only requested send offers can be deleted.');
        $this->assertTrue(TradeJoinRequest::query()->whereKey($accepted->id)->exists());

        $this->actingAs($admin, 'admin')
            ->deleteJson('http://x.sabex.lab/api/trade-joins/'.$requested->id)
            ->assertOk()
            ->assertJsonPath('ok', true);
        $this->assertFalse(TradeJoinRequest::query()->whereKey($requested->id)->exists());
        $this->assertSame(1, TradeEvent::query()
            ->where('listing_id', $listing->id)
            ->where('event_type', 'join_deleted')
            ->count());
    }

    public function test_fixture_trade_user_can_still_publish(): void
    {
        $user = TradeUser::query()->create([
            'roblox_sub' => '12345',
            'username' => 'trader',
            'display_name' => 'Trader',
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'last_login_at' => now(),
        ]);

        $this->assertTrue($user->posting_approved);

        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $this->tradePayload())
            ->assertCreated();
    }

    /**
     * @return array<string, mixed>
     */
    private function tradePayload(): array
    {
        return [
            'offering' => [['slug' => 'noobini']],
            'looking_for' => [['slug' => 'cappuccino']],
        ];
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
