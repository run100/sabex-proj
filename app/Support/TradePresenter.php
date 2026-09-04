<?php

namespace App\Support;

use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;

class TradePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function page(array $extra): array
    {
        $user = auth('trades')->user();

        return array_merge([
            'locale' => 'en',
            'hreflangLinks' => [],
            'languageLinks' => [],
            'cssHref' => SabRenderService::CSS_HREF_LARAVEL,
            'wwwOrigin' => SabHost::origin('www'),
            'tradeUser' => $user,
            'user' => $user,
            'oauthReady' => app(\App\Services\Trades\RobloxOAuthService::class)->configured(),
            'schemaMissing' => ! TradeSchema::ready(),
            'websiteJsonLd' => '',
            'unreadCount' => ($user && TradeSchema::ready())
                ? app(\App\Services\Trades\TradeNotificationService::class)->unreadCount($user)
                : 0,
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    public static function user(?TradeUser $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'roblox_sub' => $user->roblox_sub,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'avatar_url' => $user->avatar_url,
            'profile_url' => $user->profile_url ?: 'https://www.roblox.com/users/'.$user->roblox_sub.'/profile',
        ];
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
            'owner' => self::user($listing->owner),
            'counterparty' => self::user($listing->counterparty),
            'offering' => $offering->map(fn (TradeListingItem $item) => self::item($item))->all(),
            'looking_for' => $looking->map(fn (TradeListingItem $item) => self::item($item))->all(),
            'url' => '/t/'.$listing->public_id,
        ];
        if ($detail) {
            $payload['timeline'] = $listing->events
                ->sortBy('created_at')
                ->map(fn ($event) => [
                    'type' => $event->event_type,
                    'at' => optional($event->created_at)->toIso8601String(),
                    'actor' => self::user($event->actor),
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
            'requester' => self::user($request->requester),
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
            'listing_url' => $row->listing_id ? '/t/'.($row->listing?->public_id ?? '') : null,
        ];
    }
}
