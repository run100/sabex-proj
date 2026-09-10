<?php

namespace Tests\Feature;

use App\Models\SeoItem;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Services\Trades\TradeListingService;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesSabWikiTables;
use Tests\Concerns\CreatesTradeTables;
use Tests\TestCase;

class MeOpenListingsCountTest extends TestCase
{
    use CreatesSabWikiTables;
    use CreatesTradeTables;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sab.hosts.www' => 'www.sabex.lab']);
        $this->createSeoTables();
        $this->createTradeTables();
        $this->seedItem('noobini', 'Noobini', 10);
        $this->seedItem('cappuccino', 'Cappuccino', 12);
        Cache::flush();
    }

    public function test_me_returns_zero_open_listings_when_board_is_empty(): void
    {
        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.open_listings_count', 0);
    }

    public function test_me_counts_public_open_listings_for_guests_and_owners(): void
    {
        $owner = $this->tradeUser('1', 'Owner');
        app(TradeListingService::class)->create($owner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $guest = $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user', null)
            ->assertJsonPath('data.open_listings_count', 1);

        $this->actingAs($owner, 'trades')
            ->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'owner')
            ->assertJsonPath('data.open_listings_count', $guest->json('data.open_listings_count'));
    }

    public function test_me_excludes_restricted_owners_and_non_open_listings(): void
    {
        $public = $this->tradeUser('1', 'Public');
        $restricted = $this->tradeUser('2', 'Restricted');
        $restricted->moderation_status = TradeUser::MODERATION_RESTRICTED;
        $restricted->save();

        $listings = app(TradeListingService::class);
        $listings->create($public, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $listings->create($restricted, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $pendingOwner = $this->tradeUser('3', 'Pending');
        $pending = $listings->create($pendingOwner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $pending->status = TradeListing::STATUS_PENDING;
        $pending->save();

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.open_listings_count', 1);
    }

    public function test_me_counts_only_open_listings_created_in_the_last_two_days(): void
    {
        $oldOwner = $this->tradeUser('1', 'Old');
        $newOwner = $this->tradeUser('2', 'New');
        $listings = app(TradeListingService::class);
        $old = $listings->create($oldOwner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);
        $listings->create($newOwner, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $old->created_at = now()->subDays(3);
        $old->save();
        Cache::flush();

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.open_listings_count', 1);
    }

    public function test_me_reuses_cached_open_listings_count_for_two_hours(): void
    {
        $first = $this->tradeUser('1', 'First');
        app(TradeListingService::class)->create($first, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.open_listings_count', 1);

        $second = $this->tradeUser('2', 'Second');
        app(TradeListingService::class)->create($second, [['slug' => 'noobini']], [['slug' => 'cappuccino']]);

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.open_listings_count', 1);

        Cache::forget('trade:open_public_recent_count');

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.open_listings_count', 2);
    }

    private function tradeUser(string $sub, string $name): TradeUser
    {
        return TradeUser::query()->create([
            'roblox_sub' => $sub,
            'roblox_user_id' => $sub,
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
