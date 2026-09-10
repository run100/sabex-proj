<?php

namespace Tests\Feature;

use App\Exceptions\TradeException;
use App\Models\SeoItem;
use App\Models\TradeConfirmation;
use App\Models\TradeEvent;
use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeListingItemTrait;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use App\Services\Seo\SabRotCalculatorSyncService;
use App\Services\Trades\TradeJoinService;
use App\Services\Trades\TradeListingService;
use App\Services\Trades\TradeNotificationService;
use App\Support\TradeSeo;
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
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.trade.note', 'Looking for a fair swap');

        $listing = TradeListing::query()->first();
        $this->assertNotNull($listing);
        $this->assertSame('Looking for a fair swap', $listing->note);
        $this->assertSame(0, (int) $listing->sort_order);
        $this->assertSame(TradeListing::FLAG_NO, $listing->is_hot);
        $this->assertSame(TradeListing::FLAG_NO, $listing->is_top);
        $this->assertSame('win', $listing->result_snapshot);
        $this->assertSame(10.0, (float) $listing->offering_value_snapshot);
        $this->assertSame(12.0, (float) $listing->looking_value_snapshot);

        $home = $this->get('http://www.sabex.lab/trading')
            ->assertOk()
            ->assertSee('Noobini')
            ->assertSee('Cappuccino')
            ->assertSee('Trader')
            ->assertSee("They're offering")
            ->assertSee("They're looking for")
            ->assertSee('Value')
            ->assertSee('Demand')
            ->assertSee('HIGH')
            ->assertSee('Waiting for trade');
        $html = $home->getContent();
        $this->assertSame(0, substr_count($html, 'trades-item-slot--empty'));
        $this->assertSame(2, substr_count($html, 'trades-item-slot--filled'));
        $this->assertStringContainsString('trades-card-board__arrow', $html);
        preg_match('/<div class="trades-card-board__arrow"[^>]*>(.*?)<\/div>/s', $html, $cardArrowMatch);
        $cardArrowHtml = $cardArrowMatch[1] ?? '';
        $this->assertStringContainsString('/static/img/trades-transfer.png', $cardArrowHtml);
        $this->assertStringNotContainsString('<svg', $cardArrowHtml);
        $this->assertStringContainsString('trades-item-slot__mut', $html);
        $this->assertStringNotContainsString('trades-card-board__flag', $html);

        $this->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee($listing->public_id);
    }

    public function test_listing_and_confirmation_notes_reject_markup_and_keep_sql_as_text(): void
    {
        $owner = $this->tradeUser('12346', 'NoteOwner');
        $buyer = $this->tradeUser('12347', 'NoteBuyer');

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [['slug' => 'noobini']],
                'looking_for' => [['slug' => 'cappuccino']],
                'note' => '<script>alert(1)</script>',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_MESSAGE');
        $this->assertSame(0, TradeListing::query()->where('owner_user_id', $owner->id)->count());

        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $join = $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertCreated()
            ->json('data.join_request');

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/join-requests/'.$join['public_id'].'/accept')
            ->assertOk();

        $confirmUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm';
        $this->actingAs($buyer, 'trades')
            ->postJson($confirmUrl, [
                'confirmation' => 'completed',
                'note' => '<div>blocked</div>',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_MESSAGE');
        $this->assertSame(0, TradeConfirmation::query()->where('listing_id', $listing->id)->count());

        $sqlNote = '; DROP TABLE seo_trade_confirmations; --';
        $this->actingAs($buyer, 'trades')
            ->postJson($confirmUrl, [
                'confirmation' => 'completed',
                'note' => $sqlNote,
            ])
            ->assertOk();
        $this->assertSame($sqlNote, TradeConfirmation::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $buyer->id)
            ->value('note'));
    }

    public function test_user_can_post_two_trades_per_utc_day(): void
    {
        $user = $this->tradeUser('55540', 'DailyCap');
        $payload = [
            'offering' => [['slug' => 'noobini']],
            'looking_for' => [['slug' => 'cappuccino']],
        ];

        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $payload)
            ->assertCreated();
        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $payload)
            ->assertCreated();
        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $payload)
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMITED');

        $first = TradeListing::query()->where('owner_user_id', $user->id)->orderBy('id')->first();
        $this->assertNotNull($first);
        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$first->public_id.'/cancel')
            ->assertOk();
        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $payload)
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMITED');

        $this->travel(1)->days();
        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', $payload)
            ->assertCreated();
    }

    public function test_publish_persists_traits_and_shows_them_on_the_board(): void
    {
        $this->seedCalculatorMeta([
            ['name' => 'Rainbow Balloon', 'multiplier' => 6.5, 'valueMultiplier' => 1.25],
        ]);
        $user = $this->tradeUser('67890', 'TraitTrader');

        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [[
                    'slug' => 'noobini',
                    'traits' => [['name' => 'Rainbow Balloon']],
                    'trait_names' => ['Rainbow Balloon'],
                ]],
                'looking_for' => [['slug' => 'cappuccino']],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.trade.offering.0.traits.0.name', 'Rainbow Balloon');

        $listing = TradeListing::query()->with('items.traits')->first();
        $this->assertNotNull($listing);
        $offering = $listing->items->firstWhere('side', 'offering');
        $this->assertNotNull($offering);
        $this->assertSame(1, TradeListingItemTrait::query()->where('listing_item_id', $offering->id)->count());
        $this->assertSame('Rainbow Balloon', $offering->traits->first()?->trait_name_snapshot);
        $this->assertSame('Rainbow Balloon', $offering->traits->first()?->trait_name);

        $this->get('http://www.sabex.lab/trading')
            ->assertOk()
            ->assertSee('Rainbow Balloon')
            ->assertSee('+1 Traits')
            ->assertSee('Traits:');

        $this->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Rainbow Balloon')
            ->assertSee('Mutation:')
            ->assertSee('Traits:');
    }

    public function test_trade_detail_uses_truncated_title_and_show_layout(): void
    {
        $this->seedItem('garama-and-madundung', 'Garama and Madundung', 100);
        $this->seedItem('bumbatron', 'Bumbatron', 20);
        $this->seedItem('chicleteira-surfeiteira', 'Chicleteira Surfeiteira', 80);
        $this->seedItem('esok-goala', 'Esok Goala', 30);
        $user = $this->tradeUser('55501', 'DetailTrader');

        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [
                    ['slug' => 'garama-and-madundung'],
                    ['slug' => 'bumbatron'],
                    ['slug' => 'garama-and-madundung'],
                ],
                'looking_for' => [
                    ['slug' => 'chicleteira-surfeiteira'],
                    ['slug' => 'esok-goala'],
                ],
            ])
            ->assertCreated();

        $listing = TradeListing::query()->first();
        $this->assertNotNull($listing);

        $this->post('http://www.sabex.lab/logout');

        $page = $this->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('trades-show__badge--open', false)
            ->assertSee('Trade ID:')
            ->assertSee('Share Trade')
            ->assertSee("They're offering")
            ->assertSee("They're looking for")
            ->assertSee('/static/img/trades-transfer.png', false)
            ->assertSee('trades-show__grid--3', false)
            ->assertSee('Join To Trade')
            ->assertSee('Message')
            ->assertSee('Home')
            ->assertSee('All Trades')
            ->assertSee(TradeSeo::listing($listing)['h1'])
            ->assertSee('Value Calculator')
            ->assertSee('Value List')
            ->assertSee('href="/steal-a-brainrot-trading-calculator"', false)
            ->assertSee('href="/sab-value-list"', false)
            ->assertSee('Posted')
            ->assertSee('Value')
            ->assertSee('Demand')
            ->assertSee('HIGH')
            ->assertSee('Mutation:')
            ->assertSee('Default')
            ->assertSee('Traits:')
            ->assertSee('trades-show__mut', false)
            ->assertSee($listing->public_id)
            ->assertSee('/auth/roblox?return_to='.rawurlencode('/trading/'.$listing->public_id), false)
            ->assertSee('data-nav-sign-in', false)
            ->assertSee('data-roblox-auth-modal', false)
            ->assertSee('Continue with Roblox')
            ->assertDontSee('Quick replies')
            ->assertDontSee('Cancel listing')
            ->assertDontSee('trades-view-btn', false)
            ->assertDontSee('Join Trade')
            ->assertDontSee('data-offer-open', false)
            ->assertDontSee('Send an offer')
            ->assertDontSee('Posted By')
            ->assertDontSee('Accepted By');

        $html = $page->getContent();
        preg_match('/<div class="trades-show__arrow"[^>]*>(.*?)<\/div>/s', $html, $arrowMatch);
        $arrowHtml = $arrowMatch[1] ?? '';
        $this->assertStringContainsString('/static/img/trades-transfer.png', $arrowHtml);
        $this->assertStringNotContainsString('<svg', $arrowHtml);
        $this->assertStringNotContainsString('rotate(90 12 12)', $arrowHtml);

        $styles = file_get_contents(public_path('static/css/sab-trades.css'));
        $this->assertIsString($styles);
        $this->assertMatchesRegularExpression(
            '/body\.trades-app \.trades-show__arrow \{(?:(?!\}).)*width: 2\.5rem;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/body\.trades-app \.trades-show__arrow img \{(?:(?!\}).)*width: 2\.5rem;(?:(?!\}).)*height: 2\.5rem;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/body\.trades-app \.trades-show__boards \{(?:(?!\}).)*position: relative;(?:(?!\}).)*gap: 4px;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/body\.trades-app \.trades-card-board__sides \{(?:(?!\}).)*position: relative;(?:(?!\}).)*gap: 4px;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/body\.trades-app \.trades-card-board__arrow img \{(?:(?!\}).)*flex: 0 0 2\.5rem;(?:(?!\}).)*width: 2\.5rem;(?:(?!\}).)*height: 2\.5rem;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/body\.trades-app \.trades-item-grid \{(?:(?!\}).)*grid-template-columns: repeat\(3, minmax\(0, 1fr\)\);/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/body\.trades-app \.sab-bottom-nav \{\s*display: none;\s*\}/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 767px\) \{\s*body\.trades-app \{(?:(?!\}).)*padding-bottom: 0;(?:(?!\}).)*\}/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 900px\) \{.*?body\.trades-app \.trades-show__boards,\s+body\.trades-app \.trades-card-board__sides \{(?:(?!\}).)*grid-template-columns: minmax\(0, 1fr\) minmax\(0, 1fr\);(?:(?!\}).)*gap: 8px;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 900px\) \{.*?body\.trades-app \.trades-show__arrow,\s+body\.trades-app \.trades-card-board__arrow \{(?:(?!\}).)*position: absolute;(?:(?!\}).)*top: 50%;(?:(?!\}).)*left: 50%;(?:(?!\}).)*width: 8rem;(?:(?!\}).)*height: 8rem;(?:(?!\}).)*transform: translate\(-50%, -50%\);/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 900px\) \{.*?body\.trades-app \.trades-show__arrow img,\s+body\.trades-app \.trades-card-board__arrow img \{(?:(?!\}).)*flex: 0 0 8rem;(?:(?!\}).)*width: 8rem;(?:(?!\}).)*height: 8rem;/s',
            $styles
        );
        $this->assertStringContainsString('body.trades-app .trades-card-board__arrow', $styles);
        $this->assertStringContainsString('body.trades-app .trades-card-board__arrow img', $styles);
        $this->assertStringContainsString('pointer-events: none;', $styles);

        preg_match('/<title>(.*?)<\/title>/s', $html, $match);
        $documentTitle = trim(html_entity_decode(strip_tags($match[1] ?? ''), ENT_QUOTES));
        $this->assertLessThanOrEqual(60, mb_strlen($documentTitle));
        $this->assertStringStartsWith('Trading ', $documentTitle);
        $this->assertStringContainsString('SABExistCount', $documentTitle);
    }

    public function test_trade_detail_counts_view_without_timeline_event(): void
    {
        $owner = $this->tradeUser('55510', 'Viewer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $this->assertSame(0, (int) $listing->views_count);

        TradeEvent::query()->create([
            'listing_id' => $listing->id,
            'event_type' => 'trade_viewed',
            'created_at' => now(),
        ]);

        $this->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Trade posted')
            ->assertDontSee('trade_viewed');

        $listing->refresh();
        $this->assertSame(1, (int) $listing->views_count);
        $this->assertSame(1, TradeEvent::query()->where('listing_id', $listing->id)->where('event_type', 'trade_viewed')->count());

        $this->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertDontSee('trade_viewed');
        $this->assertSame(1, (int) $listing->fresh()->views_count);

        $timeline = $this->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id)
            ->assertOk()
            ->assertJsonPath('data.trade.views', 1)
            ->json('data.trade.timeline');
        $types = collect($timeline)->pluck('type');
        $this->assertTrue($types->contains('trade_posted'));
        $this->assertFalse($types->contains('trade_viewed'));
    }

    public function test_logged_in_visitor_sees_offer_and_message_modal(): void
    {
        $owner = $this->tradeUser('55502', 'Owner');
        $buyer = $this->tradeUser('55503', 'Buyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $visitor = $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Join To Trade')
            ->assertSee('Message')
            ->assertSee('data-message-open', false)
            ->assertSee('data-offer-open', false)
            ->assertSee('data-offer-form', false)
            ->assertSee('data-contact-limit="5"', false)
            ->assertSee('data-contact-remaining="5"', false)
            ->assertSee('Send an offer')
            ->assertSee('You give')
            ->assertSee('You get')
            ->assertSee("Hi! I have the items you're looking for.", false)
            ->assertSee('Quick replies')
            ->assertSee("I'm ready to trade!")
            ->assertSee('Please mark the trade as completed')
            ->assertSee('Send a message...')
            ->assertDontSee('Cancel listing')
            ->assertDontSee('Posted By')
            ->assertDontSee('Accepted By')
            ->assertDontSee('Submit report');

        $this->assertStringNotContainsString(
            'action="/api/v1/trading/trades/'.$listing->public_id.'/join" data-json-form',
            $visitor->getContent()
        );

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Cancel listing')
            ->assertDontSee('data-message-open', false)
            ->assertDontSee('Quick replies')
            ->assertDontSee('Join To Trade')
            ->assertDontSee('data-offer-open', false)
            ->assertDontSee('Send an offer')
            ->assertDontSee('Submit report')
            ->assertDontSee('Join requests')
            ->assertDontSee('No join requests yet.');
    }

    public function test_send_offer_keeps_listing_open_and_owner_can_accept_or_reject(): void
    {
        $owner = $this->tradeUser('55520', 'OfferOwner');
        $buyer = $this->tradeUser('55521', 'OfferBuyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join', [
                'note' => 'Hi! I have the items you\'re looking for.',
            ])
            ->assertCreated();

        $listing->refresh();
        $this->assertSame(TradeListing::STATUS_OPEN, $listing->status);
        $this->assertNull($listing->counterparty_user_id);

        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'JOIN_ALREADY_EXISTS');

        $page = $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Join requests')
            ->assertSee('Accept')
            ->assertSee('Reject')
            ->assertSee('trades-show__badge--open', false)
            ->assertSee('Trade ID:')
            ->assertSee('Share Trade')
            ->assertSee("Hi! I have the items you're looking for.")
            ->assertDontSee('Posted By')
            ->assertDontSee('Accepted By')
            ->assertDontSee('trades-show__badge--pending', false)
            ->assertDontSee('No join requests yet.');

        $html = $page->getContent();
        $userbarPos = strpos($html, 'trades-show__userbar');
        $joinsPos = strpos($html, 'Join requests');
        $offeringPos = strpos($html, 'trades-show__boards');
        $this->assertNotFalse($userbarPos);
        $this->assertNotFalse($joinsPos);
        $this->assertNotFalse($offeringPos);
        $this->assertGreaterThan($userbarPos, $joinsPos);
        $this->assertGreaterThan($joinsPos, $offeringPos);
        preg_match('/<section class="trades-show__joins"[^>]*>(.*?)<\/section>/s', $html, $joinsMatch);
        $this->assertStringContainsString('trades-avatar', $joinsMatch[1] ?? '');

        $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Join To Trade')
            ->assertSee('trades-show__badge--open', false)
            ->assertSee('Share Trade')
            ->assertDontSee('Posted By')
            ->assertDontSee('Participants');
    }

    public function test_accepted_listing_shows_participants_and_pending_badge(): void
    {
        $owner = $this->tradeUser('55522', 'PostedOwner');
        $buyer = $this->tradeUser('55523', 'AcceptedBuyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertCreated();

        $joinId = $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join-requests')
            ->assertOk()
            ->json('data.items.0.public_id');

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/join-requests/'.$joinId.'/accept')
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending');

        $page = $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Participants')
            ->assertSee('Posted By')
            ->assertSee('Accepted By')
            ->assertSee('PostedOwner')
            ->assertSee('AcceptedBuyer')
            ->assertSee('Pending')
            ->assertSee('Mark Completed')
            ->assertSee('Mark Failed')
            ->assertDontSee('Join requests')
            ->assertDontSee('Join To Trade')
            ->assertSee('data-message-open', false)
            ->assertSee('data-peer-name="AcceptedBuyer"', false)
            ->assertDontSee('Visitor');

        $html = $page->getContent();
        $this->assertStringContainsString('trades-show__badge--pending', $html);
        $this->assertStringNotContainsString('trades-show__badge">', $html);
        $this->assertStringNotContainsString('join-requests/', $html);
    }

    public function test_owner_can_message_accepted_counterparty(): void
    {
        $owner = $this->tradeUser('55532', 'OwnerPeer');
        $buyer = $this->tradeUser('55533', 'BuyerPeer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join')
            ->assertCreated();

        $joinId = $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join-requests')
            ->assertOk()
            ->json('data.items.0.public_id');

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/join-requests/'.$joinId.'/accept')
            ->assertOk();

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('data-message-open', false)
            ->assertSee('data-peer-name="BuyerPeer"', false);

        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/messages', [
                'message' => "I'm ready to trade!",
            ])
            ->assertCreated()
            ->assertJsonPath('data.message.message', "I'm ready to trade!")
            ->assertJsonPath('data.message.mine', true);
    }

    public function test_trade_messages_use_notifications_and_block_self_send(): void
    {
        $owner = $this->tradeUser('55504', 'Owner');
        $buyer = $this->tradeUser('55505', 'Buyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $url = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/messages';

        $this->getJson($url)->assertUnauthorized()->assertJsonPath('error.code', 'AUTH_REQUIRED');

        $this->actingAs($owner, 'trades')
            ->postJson($url, ['message' => "I'm ready to trade!"])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_MESSAGE');

        $this->actingAs($buyer, 'trades')
            ->postJson($url, ['message' => "I'm ready to trade!"])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message.message', "I'm ready to trade!")
            ->assertJsonPath('data.message.mine', true);

        $this->actingAs($buyer, 'trades')
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.message_quota.limit', 5)
            ->assertJsonPath('data.message_quota.sent', 1)
            ->assertJsonPath('data.message_quota.remaining', 4)
            ->assertJsonPath('data.contact_quota.limit', 5)
            ->assertJsonPath('data.contact_quota.sent', 1)
            ->assertJsonPath('data.contact_quota.remaining', 4)
            ->assertJsonPath('data.items.0.message', "I'm ready to trade!")
            ->assertJsonPath('data.items.0.mine', true);

        $this->actingAs($owner, 'trades')
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.items.0.message', "I'm ready to trade!")
            ->assertJsonPath('data.items.0.mine', false);

        $this->actingAs($owner, 'trades')
            ->postJson($url, ['message' => "I'll join you!"])
            ->assertCreated()
            ->assertJsonPath('data.message.message', "I'll join you!")
            ->assertJsonPath('data.message.mine', true);

        $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.items.0.message', "I'm ready to trade!");

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/notifications')
            ->assertOk()
            ->assertSee("I'm ready to trade!")
            ->assertSee('Trade ID:')
            ->assertSee('#'.$listing->public_id)
            ->assertSee('trades-avatar', false);

        $this->assertSame(2, TradeNotification::query()->where('listing_id', $listing->id)->where('type', 'trade_message')->count());
        $this->assertSame(1, TradeNotification::query()->where('user_id', $owner->id)->where('type', 'trade_message')->count());
        $this->assertSame(1, TradeNotification::query()->where('user_id', $buyer->id)->where('type', 'trade_message')->count());
    }

    public function test_trade_messages_reject_markup_and_enforce_per_listing_directional_limit(): void
    {
        $owner = $this->tradeUser('55506', 'MessageOwner');
        $buyer = $this->tradeUser('55507', 'MessageBuyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $url = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/messages';

        foreach (['<script></script>', "<script>alert('1')</script>", '<div>blocked</div>', '<!-- blocked -->'] as $message) {
            $this->actingAs($buyer, 'trades')
                ->postJson($url, ['message' => $message])
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'INVALID_MESSAGE');
        }
        $this->assertSame(0, TradeNotification::query()->where('listing_id', $listing->id)->where('type', 'trade_message')->count());

        foreach (['First message', 'Second message', 'Third message', 'Fourth message', 'Fifth message'] as $message) {
            $this->actingAs($buyer, 'trades')
                ->postJson($url, ['message' => $message])
                ->assertCreated();
        }

        $this->actingAs($buyer, 'trades')
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.message_quota.limit', 5)
            ->assertJsonPath('data.message_quota.sent', 5)
            ->assertJsonPath('data.message_quota.remaining', 0);

        $this->actingAs($buyer, 'trades')
            ->postJson($url, ['message' => 'Sixth message'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'MESSAGE_LIMIT_REACHED');

        $otherListing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $otherUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$otherListing->public_id.'/messages';
        $this->actingAs($buyer, 'trades')
            ->postJson($otherUrl, ['message' => 'Another trade'])
            ->assertCreated();

        $this->actingAs($buyer, 'trades')
            ->getJson($otherUrl)
            ->assertOk()
            ->assertJsonPath('data.contact_quota.limit', 5)
            ->assertJsonPath('data.contact_quota.sent', 1)
            ->assertJsonPath('data.contact_quota.remaining', 4);

        $this->assertSame(6, TradeNotification::query()
            ->where('type', TradeNotificationService::TYPE_MESSAGE)
            ->where('actor_user_id', $buyer->id)
            ->where('user_id', $owner->id)
            ->count());

        foreach (['Reply one', 'Reply two', 'Reply three', 'Reply four', 'Reply five'] as $message) {
            $this->actingAs($owner, 'trades')
                ->postJson($url, ['message' => $message])
                ->assertCreated();
        }

        $this->actingAs($owner, 'trades')
            ->postJson($url, ['message' => 'Reply six'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'MESSAGE_LIMIT_REACHED');

        $this->assertSame(5, TradeNotification::query()
            ->where('type', TradeNotificationService::TYPE_MESSAGE)
            ->where('actor_user_id', $owner->id)
            ->where('user_id', $buyer->id)
            ->where('listing_id', $listing->id)
            ->count());
    }

    public function test_join_notes_use_shared_text_policy_and_contact_quota(): void
    {
        $owner = $this->tradeUser('55518', 'JoinOwner');
        $buyer = $this->tradeUser('55519', 'JoinBuyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $otherListing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $url = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/join';

        foreach (['<script></script>', '&lt;script&gt;', "Tab\tbed", 'Hello 😀', "Hello\u{200B}World", '1=1'] as $note) {
            $this->actingAs($buyer, 'trades')
                ->postJson($url, ['note' => $note])
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'INVALID_MESSAGE');
        }
        $this->assertSame(0, TradeJoinRequest::query()->where('requester_user_id', $buyer->id)->count());

        $sqlText = '中文消息 123，。！？; DROP TABLE seo_trade_users; --';
        $this->actingAs($buyer, 'trades')
            ->postJson($url, ['note' => $sqlText])
            ->assertCreated()
            ->assertJsonPath('data.join_request.note', $sqlText);
        $joinRequest = TradeJoinRequest::query()
            ->where('requester_user_id', $buyer->id)
            ->where('owner_user_id', $owner->id)
            ->firstOrFail();
        $joinRequest->status = TradeJoinRequest::STATUS_REJECTED;
        $joinRequest->rejected_at = now();
        $joinRequest->save();

        $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('data-contact-remaining="4"', false);

        $messageUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/messages';
        $this->actingAs($buyer, 'trades')
            ->postJson($messageUrl, ['message' => '中文消息 123，。！？'])
            ->assertCreated();

        $this->actingAs($owner, 'trades')
            ->postJson($messageUrl, ['message' => 'Reply'])
            ->assertCreated();

        foreach (['Third interaction', 'Fourth interaction', 'Fifth interaction'] as $message) {
            $this->actingAs($buyer, 'trades')
                ->postJson($messageUrl, ['message' => $message])
                ->assertCreated();
        }

        $this->actingAs($buyer, 'trades')
            ->getJson($messageUrl)
            ->assertOk()
            ->assertJsonPath('data.message_quota.limit', 5)
            ->assertJsonPath('data.message_quota.sent', 4)
            ->assertJsonPath('data.message_quota.remaining', 1)
            ->assertJsonPath('data.contact_quota.sent', 5)
            ->assertJsonPath('data.contact_quota.remaining', 0);

        $otherJoinUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$otherListing->public_id.'/join';
        $this->actingAs($buyer, 'trades')
            ->postJson($otherJoinUrl, ['note' => 'Another request'])
            ->assertCreated();

        $this->actingAs($buyer, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$otherListing->public_id.'/messages')
            ->assertOk()
            ->assertJsonPath('data.contact_quota.sent', 1)
            ->assertJsonPath('data.contact_quota.remaining', 4);

        $this->actingAs($buyer, 'trades')
            ->postJson($messageUrl, ['message' => 'Sixth interaction'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'MESSAGE_LIMIT_REACHED');

        $this->assertSame(2, TradeJoinRequest::query()
            ->where('requester_user_id', $buyer->id)
            ->where('owner_user_id', $owner->id)
            ->count());
        $this->assertSame(4, TradeNotification::query()
            ->where('actor_user_id', $buyer->id)
            ->where('user_id', $owner->id)
            ->where('type', TradeNotificationService::TYPE_MESSAGE)
            ->where('listing_id', $listing->id)
            ->count());
    }

    public function test_contact_quota_is_per_listing_and_empty_join_note_remains_allowed(): void
    {
        $owner = $this->tradeUser('55522', 'TwoJoinOwner');
        $buyer = $this->tradeUser('55523', 'TwoJoinBuyer');
        $first = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $second = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $firstJoinUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$first->public_id.'/join';
        $join = $this->actingAs($buyer, 'trades')
            ->postJson($firstJoinUrl)
            ->assertCreated()
            ->json('data.join_request');
        $this->assertNull($join['note']);
        $joinRequest = TradeJoinRequest::query()->where('public_id', $join['public_id'])->firstOrFail();
        $joinRequest->status = TradeJoinRequest::STATUS_REJECTED;
        $joinRequest->rejected_at = now();
        $joinRequest->save();

        $messageUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$first->public_id.'/messages';
        foreach (['Message one', 'Message two', 'Message three', 'Message four'] as $message) {
            $this->actingAs($buyer, 'trades')
                ->postJson($messageUrl, ['message' => $message])
                ->assertCreated();
        }

        $this->actingAs($buyer, 'trades')
            ->getJson($messageUrl)
            ->assertOk()
            ->assertJsonPath('data.contact_quota.limit', 5)
            ->assertJsonPath('data.contact_quota.sent', 5)
            ->assertJsonPath('data.contact_quota.remaining', 0);

        $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/trading/'.$first->public_id)
            ->assertOk()
            ->assertSee('data-contact-remaining="0"', false);

        $this->actingAs($buyer, 'trades')
            ->postJson($firstJoinUrl, ['note' => 'Sixth interaction'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'JOIN_LIMIT_REACHED');

        $secondJoinUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$second->public_id.'/join';
        $this->actingAs($buyer, 'trades')
            ->postJson($secondJoinUrl)
            ->assertCreated();

        $this->actingAs($buyer, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$second->public_id.'/messages')
            ->assertOk()
            ->assertJsonPath('data.contact_quota.sent', 1)
            ->assertJsonPath('data.contact_quota.remaining', 4);

        $this->actingAs($buyer, 'trades')
            ->postJson($secondJoinUrl, ['note' => str_repeat('a', 281)])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_MESSAGE');

        $this->assertSame(2, TradeJoinRequest::query()
            ->where('requester_user_id', $buyer->id)
            ->where('owner_user_id', $owner->id)
            ->count());
    }

    public function test_trade_messages_reject_non_text_angle_characters(): void
    {
        $owner = $this->tradeUser('55508', 'AngleOwner');
        $buyer = $this->tradeUser('55509', 'AngleBuyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $url = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/messages';

        foreach (['<3', '2 < 3', '&lt;script&gt;'] as $message) {
            $this->actingAs($buyer, 'trades')
                ->postJson($url, ['message' => $message])
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'INVALID_MESSAGE');
        }

        $this->assertSame(0, TradeNotification::query()
            ->where('listing_id', $listing->id)
            ->where('type', TradeNotificationService::TYPE_MESSAGE)
            ->count());
    }

    public function test_trade_messages_allow_unicode_text_and_common_punctuation(): void
    {
        $owner = $this->tradeUser('55514', 'NormalOwner');
        $buyer = $this->tradeUser('55515', 'NormalBuyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $url = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/messages';

        foreach (["I'm ready to trade!", "中文消息 123，。！？\n下一行"] as $message) {
            $this->actingAs($buyer, 'trades')
                ->postJson($url, ['message' => $message])
                ->assertCreated()
                ->assertJsonPath('data.message.message', $message);
        }
    }

    public function test_trade_messages_reject_non_text_characters_without_consuming_quota(): void
    {
        $owner = $this->tradeUser('55516', 'CharacterOwner');
        $buyer = $this->tradeUser('55517', 'CharacterBuyer');
        $listing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $url = 'http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/messages';

        foreach (['Hello 😀', "Tab\tbed", "Hello\u{200B}World", "\x01control", '1=1', '2+2', '$100', 'C:\\temp'] as $message) {
            $response = $this->actingAs($buyer, 'trades')
                ->postJson($url, ['message' => $message]);
            $this->assertSame(422, $response->status(), 'Unexpectedly accepted: '.json_encode($message, JSON_UNESCAPED_UNICODE));
            $response->assertJsonPath('error.code', 'INVALID_MESSAGE');
        }

        $this->assertSame(0, TradeNotification::query()
            ->where('listing_id', $listing->id)
            ->where('type', TradeNotificationService::TYPE_MESSAGE)
            ->count());
    }

    public function test_public_trade_inputs_treat_sql_payloads_as_values(): void
    {
        $owner = $this->tradeUser('55511', 'SqlOwner');
        $buyer = $this->tradeUser('55512', 'SqlBuyer');
        $older = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $newer = app(TradeListingService::class)->create($buyer, [['slug' => 'cappuccino']], [['slug' => 'noobini']]);
        $older->created_at = now()->subMinute();
        $older->save();
        $newer->created_at = now();
        $newer->save();

        $injection = "' OR 1=1 UNION SELECT NULL --";
        $sortInjection = 'created_at desc, (select 1)';

        $this->getJson('http://www.sabex.lab/api/v1/brainrots/search?q='.rawurlencode($injection))
            ->assertOk()
            ->assertJsonPath('data.items', []);

        $this->getJson('http://www.sabex.lab/api/v1/trading/trades?sort='.rawurlencode($sortInjection)
            .'&page='.rawurlencode('1 OR 1=1')
            .'&limit='.rawurlencode('20 OR 1=1'))
            ->assertOk()
            ->assertJsonPath('data.items.0.public_id', $newer->public_id);

        $this->getJson('http://www.sabex.lab/api/v1/trading/trades/completed?username='.rawurlencode($injection)
            .'&roblox_sub='.rawurlencode('; DROP TABLE seo_trade_users; --'))
            ->assertOk()
            ->assertJsonPath('data.items', []);

        $submitter = $this->tradeUser('55513', 'SqlSubmitter');
        $this->actingAs($submitter, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [['slug' => 'noobini']],
                'looking_for' => [['slug' => 'cappuccino']],
                'note' => $injection,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_MESSAGE');

        $listingSqlText = '; DROP TABLE seo_trade_users; --';
        $this->actingAs($submitter, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [['slug' => 'noobini']],
                'looking_for' => [['slug' => 'cappuccino']],
                'note' => $listingSqlText,
            ])
            ->assertCreated()
            ->assertJsonPath('data.trade.note', $listingSqlText);

        $submitted = TradeListing::query()->where('owner_user_id', $submitter->id)->latest('id')->firstOrFail();
        $this->assertSame($listingSqlText, $submitted->note);

        $messageUrl = 'http://www.sabex.lab/api/v1/trading/trades/'.$older->public_id.'/messages';
        $this->actingAs($buyer, 'trades')
            ->postJson($messageUrl, ['message' => '; DROP TABLE seo_trade_notifications; --'])
            ->assertCreated()
            ->assertJsonPath('data.message.message', '; DROP TABLE seo_trade_notifications; --');
    }

    public function test_unknown_trait_is_rejected_and_not_stored(): void
    {
        $this->seedCalculatorMeta([
            ['name' => 'Rainbow Balloon', 'multiplier' => 6.5, 'valueMultiplier' => 1.25],
        ]);
        $user = $this->tradeUser('11111', 'BadTrait');

        $this->actingAs($user, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades', [
                'offering' => [[
                    'slug' => 'noobini',
                    'traits' => [['name' => 'Not A Real Trait']],
                ]],
                'looking_for' => [['slug' => 'cappuccino']],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_TRAIT');

        $this->assertSame(0, TradeListing::query()->count());
        $this->assertSame(0, TradeListingItemTrait::query()->count());
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
        $this->expectException(TradeException::class);
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

    public function test_repeat_confirm_does_not_spam_timeline_and_waits_for_other_party(): void
    {
        $owner = $this->tradeUser('55530', 'WaitOwner');
        $buyer = $this->tradeUser('55531', 'WaitBuyer');
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
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending_confirmation');
        $this->actingAs($owner, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending_confirmation');

        $this->assertSame(1, TradeEvent::query()->where('listing_id', $listing->id)->where('event_type', 'confirmation_completed')->count());
        $this->assertSame(1, TradeNotification::query()->where('listing_id', $listing->id)->where('type', 'other_user_confirmed')->count());

        $waitingTimeline = $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id)
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'pending_confirmation')
            ->json('data.trade.timeline');
        $waitingTypes = collect($waitingTimeline)->pluck('type');
        $this->assertSame($waitingTypes->count(), $waitingTypes->unique()->count());
        $this->assertTrue(collect($waitingTimeline)->pluck('label')->contains('Marked completed'));

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Waiting for WaitBuyer to confirm')
            ->assertSee('Awaiting confirmation')
            ->assertSee('Trade pending')
            ->assertDontSee('Mark Completed')
            ->assertSee('Mark Failed')
            ->assertDontSee('confirmation_completed');

        $this->actingAs($buyer, 'trades')
            ->postJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id.'/confirm', ['confirmation' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'completed');

        $this->assertSame(1, TradeEvent::query()->where('listing_id', $listing->id)->where('event_type', 'trade_completed')->count());

        $doneTimeline = $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/trading/trades/'.$listing->public_id)
            ->assertOk()
            ->assertJsonPath('data.trade.status', 'completed')
            ->json('data.trade.timeline');
        $doneTypes = collect($doneTimeline)->pluck('type');
        $this->assertSame($doneTypes->count(), $doneTypes->unique()->count());
        $this->assertTrue(collect($doneTimeline)->pluck('label')->contains('Trade completed'));

        $page = $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/trading/'.$listing->public_id)
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('Trade completed')
            ->assertDontSee('Mark Completed')
            ->assertDontSee('Mark Failed')
            ->assertDontSee('Waiting for');

        $this->assertSame(0, substr_count($page->getContent(), 'Marked completed'));
        $this->assertSame(1, substr_count($page->getContent(), 'Trade completed'));
        $this->assertSame(1, substr_count($page->getContent(), 'Trade posted'));
        $this->assertSame(1, substr_count($page->getContent(), 'Trade joined'));
        $this->assertSame(1, substr_count($page->getContent(), 'Trade pending'));
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

    public function test_profile_and_offers_breadcrumbs_follow_profile_visibility(): void
    {
        $user = $this->tradeUser('50', 'BreadcrumbUser');

        $profilePage = $this->get('http://www.sabex.lab/profile/'.$user->profile_id)
            ->assertOk()
            ->assertSee('trades-profile-stats', false);
        preg_match('/<nav class="trades-show__back"[^>]*>(.*?)<\/nav>/s', $profilePage->getContent(), $profileMatch);
        $profileBreadcrumb = $profileMatch[1] ?? '';
        $this->assertStringContainsString('href="/"', $profileBreadcrumb);
        $this->assertStringContainsString('>Profile</span>', $profileBreadcrumb);
        $this->assertStringContainsString('trades-show__back-current">BreadcrumbUser</span>', $profileBreadcrumb);
        $this->assertStringNotContainsString('All Trades', $profileBreadcrumb);

        $offersPage = $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers')
            ->assertOk();
        preg_match('/<nav class="trades-show__back"[^>]*>(.*?)<\/nav>/s', $offersPage->getContent(), $offersMatch);
        $offersBreadcrumb = $offersMatch[1] ?? '';
        $this->assertStringContainsString('href="'.$user->profilePath().'"', $offersBreadcrumb);
        $this->assertStringContainsString("BreadcrumbUser's Profile", html_entity_decode($offersBreadcrumb, ENT_QUOTES, 'UTF-8'));
        $this->assertStringContainsString('trades-show__back-current">Offers</span>', $offersBreadcrumb);

        $user->profile_visibility = TradeUser::VISIBILITY_PRIVATE;
        $user->save();

        $privateOffersPage = $this->actingAs($user->fresh(), 'trades')
            ->get('http://www.sabex.lab/user/offers')
            ->assertOk();
        preg_match('/<nav class="trades-show__back"[^>]*>(.*?)<\/nav>/s', $privateOffersPage->getContent(), $privateOffersMatch);
        $privateOffersBreadcrumb = $privateOffersMatch[1] ?? '';
        $this->assertStringContainsString('href="/user"', $privateOffersBreadcrumb);
        $this->assertStringContainsString('Your Account', $privateOffersBreadcrumb);
        $this->assertStringNotContainsString($user->profilePath(), $privateOffersBreadcrumb);
    }

    public function test_activity_page_copy_requires_login(): void
    {
        $this->get('http://www.sabex.lab/user/offers')
            ->assertRedirect('/auth/roblox?return_to='.urlencode('/user/offers'));
        $this->get('http://www.sabex.lab/trading/completed')
            ->assertOk()
            ->assertSee('Completed Steal a Brainrot Trades');

        $user = $this->tradeUser('51', 'Watcher');
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers')
            ->assertOk()
            ->assertSee('<title>SABExistCount - Steal a Brainrot Offers</title>', false)
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('<h1>Offers</h1>', false)
            ->assertSee('Post a trade ad')
            ->assertSee('Accept or decline offers on your trade ads before a chat is opened.')
            ->assertSee('No offers waiting')
            ->assertSee('href="/user/offers?status=ads"', false)
            ->assertSee('href="/user/offers?status=received"', false)
            ->assertSee('href="/user/offers?status=sent"', false)
            ->assertSee('href="/user/offers?status=pending"', false)
            ->assertSee('href="/user/offers?status=completed"', false)
            ->assertSee('href="/user/offers?status=expired"', false)
            ->assertDontSee('href="/user/offers?status=all"', false)
            ->assertDontSee('<h1>Steal a Brainrot Trade Activity</h1>', false)
            ->assertSee('Home')
            ->assertSee("Watcher's Profile")
            ->assertSee('href="'.$user->profilePath().'"', false)
            ->assertSee('Value Calculator')
            ->assertSee('Value List')
            ->assertSee('href="/steal-a-brainrot-trading-calculator"', false)
            ->assertSee('href="/sab-value-list"', false);

        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=ads')
            ->assertOk()
            ->assertSee('Trade ads you posted. Open one to manage it or wait for offers.')
            ->assertSee('No ads yet');
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=sent')
            ->assertOk()
            ->assertSee("Offers you sent on someone else's trade ad");
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=completed')
            ->assertOk()
            ->assertSee('Trades you finished after both sides marked completed.');
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=expired')
            ->assertOk()
            ->assertSee('Trade ads that timed out before both sides finished');
        $this->actingAs($user, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=pending')
            ->assertOk()
            ->assertSee('Trades in progress after an offer was accepted. Finish in Roblox, then both sides mark completed.')
            ->assertSee('No pending trades');
    }

    public function test_activity_tabs_count_open_listing_only_in_all(): void
    {
        $this->raiseTradePostLimits();
        $owner = $this->tradeUser('52', 'Poster');
        $buyer = $this->tradeUser('53', 'OfferBuyer');
        $ownListing = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $theirListing = app(TradeListingService::class)->create($buyer, [['slug' => 'cappuccino']], [['slug' => 'noobini']]);

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers')
            ->assertOk()
            ->assertSee('All Ads')
            ->assertSee('Received')
            ->assertSee('Sent')
            ->assertSee('Pending')
            ->assertSee('Completed')
            ->assertSee('Expired')
            ->assertSee('No offers waiting')
            ->assertDontSee($ownListing->public_id)
            ->assertDontSee($theirListing->public_id)
            ->assertDontSee('href="/user/offers?status=all"', false);

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=ads')
            ->assertOk()
            ->assertSee('Trade ads you posted. Open one to manage it or wait for offers.')
            ->assertSee($ownListing->public_id)
            ->assertDontSee($theirListing->public_id);

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=pending')
            ->assertOk()
            ->assertSee('Trades in progress after an offer was accepted. Finish in Roblox, then both sides mark completed.')
            ->assertDontSee($ownListing->public_id)
            ->assertDontSee($theirListing->public_id);

        $inProgress = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $inProgress->forceFill([
            'status' => 'pending',
            'counterparty_user_id' => $buyer->id,
        ])->save();

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=pending')
            ->assertOk()
            ->assertSee($inProgress->public_id)
            ->assertDontSee($ownListing->public_id);

        $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=pending')
            ->assertOk()
            ->assertSee($inProgress->public_id)
            ->assertDontSee($ownListing->public_id);

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=received')
            ->assertOk()
            ->assertDontSee($inProgress->public_id);

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=sent')
            ->assertOk()
            ->assertDontSee($inProgress->public_id);

        $done = app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $done->forceFill(['status' => 'completed'])->save();

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=completed')
            ->assertOk()
            ->assertSee('Trades you finished after both sides marked completed.')
            ->assertSee($done->public_id);

        app(TradeJoinService::class)->join($buyer, $ownListing);
        app(TradeJoinService::class)->join($owner, $theirListing);

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=received')
            ->assertOk()
            ->assertSee($ownListing->public_id)
            ->assertDontSee($theirListing->public_id);

        $this->actingAs($owner, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=sent')
            ->assertOk()
            ->assertSee("Offers you sent on someone else's trade ad")
            ->assertSee($theirListing->public_id)
            ->assertDontSee($ownListing->public_id)
            ->assertDontSee('No offers waiting');
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
        $owner = $this->tradeUser('63', 'ActiveOwner');
        $buyer = $this->tradeUser('64', 'ActiveBuyer');
        [$oldest, $newest] = $this->createNumberedListings($owner, 21);
        $joins = app(TradeJoinService::class);
        foreach (TradeListing::query()->orderBy('id')->get() as $listing) {
            $joins->join($buyer, $listing);
        }

        $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=sent')
            ->assertOk()
            ->assertSee($newest->public_id)
            ->assertDontSee($oldest->public_id)
            ->assertSee('page=2', false)
            ->assertSee('status=sent', false)
            ->assertSee('trades-pagination', false);

        $this->actingAs($buyer, 'trades')
            ->get('http://www.sabex.lab/user/offers?status=sent&page=2')
            ->assertOk()
            ->assertSee($oldest->public_id)
            ->assertDontSee($newest->public_id)
            ->assertSee('status=sent', false);
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
            'sab-trades.post_trade_limit_per_day' => 50,
            'sab-trades.join_limit_per_hour' => 50,
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

    /**
     * @param  list<array{name: string, multiplier?: float, valueMultiplier?: float}>  $traits
     */
    private function seedCalculatorMeta(array $traits): void
    {
        $path = SabRotCalculatorSyncService::calculatorMetaPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, json_encode([
            'traits' => $traits,
            'streakMultipliers' => ['3' => 2, '6' => 3],
        ], JSON_THROW_ON_ERROR));
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
