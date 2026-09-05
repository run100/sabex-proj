<?php

namespace App\Support;

use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use App\Services\Trades\RobloxOAuthService;
use App\Services\Trades\TradeNotificationService;

class TradePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function page(array $extra): array
    {
        $user = auth('trades')->user();
        $wwwOrigin = SabHost::origin('www');
        $unread = ($user && TradeSchema::ready())
            ? app(TradeNotificationService::class)->unreadCount($user)
            : 0;

        return array_merge([
            'locale' => 'en',
            'hreflangLinks' => [],
            'languageLinks' => [],
            't' => SabRenderService::getI18nEn(),
            'urlPrefix' => $wwwOrigin,
            'productUrlPrefix' => $wwwOrigin,
            'cssHref' => SabRenderService::CSS_HREF_LARAVEL,
            'wwwOrigin' => $wwwOrigin,
            'tradeUser' => $user,
            'user' => $user,
            'oauthReady' => app(RobloxOAuthService::class)->configured(),
            'emailLoginAllowed' => TradeEmailAuth::loginAllowed(),
            'localAuthEnabled' => TradeLocalAuth::enabled(),
            'schemaMissing' => ! TradeSchema::ready(),
            'identitiesReady' => TradeSchema::identitiesReady(),
            'websiteJsonLd' => '',
            'unreadCount' => $unread,
            'tradesDrawerItems' => self::drawerItems($user, $unread),
        ], $extra);
    }

    /**
     * @return list<array{href: string, label: string, icon: string, active: bool, method?: string}>
     */
    public static function drawerItems(?TradeUser $user, int $unread = 0): array
    {
        $path = '/'.trim(request()->path(), '/');
        $items = [
            [
                'href' => TradePaths::create(),
                'label' => 'Post',
                'icon' => 'post',
                'active' => $path === TradePaths::create(),
            ],
            [
                'href' => TradePaths::activity(),
                'label' => 'Activity',
                'icon' => 'clock',
                'active' => $path === TradePaths::activity(),
            ],
        ];
        if ($user) {
            $items[] = [
                'href' => TradePaths::notifications(),
                'label' => $unread > 0 ? 'Alerts ('.$unread.')' : 'Alerts',
                'icon' => 'bell',
                'active' => $path === TradePaths::notifications(),
            ];
            $items[] = [
                'href' => $user->profilePath(),
                'label' => $user->display_name ?: $user->username,
                'icon' => 'user',
                'active' => $path === $user->profilePath(),
            ];
            $items[] = [
                'href' => TradePaths::account(),
                'label' => 'Account',
                'icon' => 'settings',
                'active' => $path === TradePaths::account(),
            ];
            $items[] = [
                'href' => TradePaths::logout(),
                'label' => 'Sign out',
                'icon' => 'logout',
                'active' => false,
                'method' => 'post',
            ];
        } else {
            $items[] = [
                'href' => TradePaths::robloxLogin(),
                'label' => 'Sign in',
                'icon' => 'login',
                'active' => str_starts_with($path, '/auth'),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function userPublic(?TradeUser $user): ?array
    {
        if (! TradeProfileAccess::canShowPublicIdentity($user)) {
            return null;
        }

        $payload = [
            'profile_id' => $user->profile_id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'avatar_url' => $user->avatar_url,
        ];
        if (TradeProfileAccess::canAccessProfile($user)) {
            $payload['profile_path'] = $user->profilePath();
            $robloxUrl = $user->robloxProfileUrl();
            if ($robloxUrl !== null) {
                $payload['profile_url'] = $robloxUrl;
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function userPrivate(?TradeUser $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'profile_id' => $user->profile_id,
            'public_id' => $user->public_id,
            'email' => $user->email,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'avatar_url' => $user->avatar_url,
            'profile_path' => $user->profilePath(),
            'profile_url' => $user->robloxProfileUrl(),
            'providers' => $user->connectedProviders(),
            'profile_visibility' => $user->profile_visibility,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(?TradeUser $user): ?array
    {
        return self::userPrivate($user);
    }

    /**
     * @return array<string, mixed>
     */
    public static function listing(TradeListing $listing, bool $detail = false): array
    {
        $offering = $listing->items->where('side', TradeListingItem::SIDE_OFFERING)->sortBy('slot_no')->values();
        $looking = $listing->items->where('side', TradeListingItem::SIDE_LOOKING)->sortBy('slot_no')->values();
        $payload = [
            'public_id' => $listing->public_id,
            'status' => $listing->status,
            'wfl' => $listing->result_snapshot,
            'offering_value' => (float) $listing->offering_value_snapshot,
            'looking_value' => (float) $listing->looking_value_snapshot,
            'difference' => (float) $listing->value_difference_snapshot,
            'difference_percent' => $listing->difference_percent_snapshot,
            'note' => $listing->note,
            'views' => (int) $listing->views_count,
            'created_at' => optional($listing->created_at)->toIso8601String(),
            'expires_at' => optional($listing->expires_at)->toIso8601String(),
            'owner' => self::userPublic($listing->owner),
            'counterparty' => self::userPublic($listing->counterparty),
            'offering' => $offering->map(fn (TradeListingItem $item) => self::item($item))->all(),
            'looking_for' => $looking->map(fn (TradeListingItem $item) => self::item($item))->all(),
            'url' => TradePaths::show($listing->public_id),
        ];
        if ($detail) {
            $payload['timeline'] = $listing->events
                ->sortBy('created_at')
                ->map(fn ($event) => [
                    'type' => $event->event_type,
                    'at' => optional($event->created_at)->toIso8601String(),
                    'actor' => self::userPublic($event->actor),
                ])->values()->all();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(TradeListingItem $item): array
    {
        return [
            'slot_no' => $item->slot_no,
            'seo_item_id' => $item->seo_item_id,
            'slug' => $item->slug_snapshot,
            'name' => $item->brainrot_name_snapshot,
            'image' => $item->image_url_snapshot,
            'mutation' => $item->mutation_name_snapshot,
            'value' => (float) $item->final_value_snapshot,
            'income' => $item->final_income_snapshot,
            'demand' => $item->demand_snapshot,
            'exist_count' => $item->exist_count_snapshot,
            'traits' => $item->traits->map(fn ($trait) => [
                'name' => $trait->trait_name_snapshot,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function join(TradeJoinRequest $request): array
    {
        return [
            'public_id' => $request->public_id,
            'status' => $request->status,
            'note' => $request->note,
            'created_at' => optional($request->created_at)->toIso8601String(),
            'requester' => self::userPublic($request->requester),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function notification(TradeNotification $row): array
    {
        return [
            'id' => $row->id,
            'type' => $row->type,
            'title' => $row->title,
            'message' => $row->message,
            'is_read' => $row->is_read,
            'created_at' => optional($row->created_at)->toIso8601String(),
            'listing_url' => $row->listing_id && $row->listing
                ? TradePaths::show($row->listing->public_id)
                : null,
        ];
    }
}
