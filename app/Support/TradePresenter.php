<?php

namespace App\Support;

use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use App\Services\Seo\SabRenderService;
use App\Services\Trades\RobloxOAuthService;

class TradePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function page(array $extra): array
    {
        $user = auth('trades')->user();
        $wwwOrigin = SabHost::origin('www');

        return array_merge([
            'locale' => 'en',
            'hreflangLinks' => [],
            'languageLinks' => self::homeLanguageLinks(),
            't' => SabRenderService::getI18nEn(),
            'urlPrefix' => $wwwOrigin,
            'productUrlPrefix' => $wwwOrigin,
            'cssHref' => SabRenderService::CSS_HREF_LARAVEL,
            'wwwOrigin' => $wwwOrigin,
            'tradeUser' => $user,
            'user' => $user,
            'oauthReady' => app(RobloxOAuthService::class)->configured(),
            'oauthUnavailableCopy' => self::oauthUnavailableCopy(),
            'emailLoginAllowed' => TradeEmailAuth::loginAllowed(),
            'localAuthEnabled' => TradeLocalAuth::enabled(),
            'schemaMissing' => ! TradeSchema::ready(),
            'identitiesReady' => TradeSchema::identitiesReady(),
            'websiteJsonLd' => '',
            'unreadCount' => 0,
            'tradesDrawerItems' => self::drawerItems(null),
        ], $extra);
    }

    /**
     * Same language switch as the English homepage: EN → `/`, others → `/pt` etc.
     *
     * @return list<array{locale: string, label: string, href: string, active: bool}>
     */
    public static function homeLanguageLinks(): array
    {
        return array_map(static fn (string $locale): array => [
            'locale' => $locale,
            'label' => SabRenderService::localeLabel($locale),
            'href' => $locale === 'en' ? '/' : '/'.$locale,
            'active' => $locale === 'en',
        ], SabRenderService::MULTILINGUAL_PAGE_LOCALES);
    }

    public static function compactValue(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $number = (float) $value;
        $abs = abs($number);
        if ($abs >= 1_000_000) {
            return rtrim(rtrim(number_format($number / 1_000_000, 1, '.', ''), '0'), '.').'M';
        }
        if ($abs >= 1_000) {
            return rtrim(rtrim(number_format($number / 1_000, 1, '.', ''), '0'), '.').'k';
        }

        return number_format($number);
    }

    public static function demandLabel(?string $demand): string
    {
        $demand = trim((string) $demand);

        return $demand !== '' ? $demand : '—';
    }

    public static function demandTone(?string $demand): string
    {
        return match (strtoupper(trim((string) $demand))) {
            'TERRIBLE' => 'terrible',
            'LOW' => 'low',
            'MEDIUM' => 'medium',
            'HIGH' => 'high',
            'VERY HIGH' => 'very-high',
            default => 'unknown',
        };
    }

    /**
     * @param  iterable<int, TradeListingItem>  $items
     */
    public static function sideDemand(iterable $items): string
    {
        $labels = [];
        foreach ($items as $item) {
            $demand = trim((string) ($item->demand_snapshot ?? ''));
            if ($demand !== '') {
                $labels[] = $demand;
            }
        }
        if ($labels === []) {
            return '—';
        }
        $counts = array_count_values($labels);
        arsort($counts);

        return (string) array_key_first($counts);
    }

    public static function wflLabel(?string $wfl): string
    {
        return match ($wfl) {
            'win' => 'WIN',
            'fair' => 'FAIR',
            'lose' => 'LOSE',
            default => '',
        };
    }

    public static function eventLabel(string $type): string
    {
        return match ($type) {
            'trade_posted' => 'Trade posted',
            'join_requested' => 'Offer sent',
            'join_accepted' => 'Offer accepted',
            'join_rejected' => 'Offer rejected',
            'join_cancelled' => 'Offer cancelled',
            'join_deleted' => 'Offer removed',
            'trade_pending' => 'Trade pending',
            'confirmation_completed' => 'Marked completed',
            'confirmation_failed' => 'Marked failed',
            'trade_completed' => 'Trade completed',
            'trade_failed' => 'Trade failed',
            'trade_disputed' => 'Trade disputed',
            'trade_cancelled' => 'Trade cancelled',
            default => str_replace('_', ' ', $type),
        };
    }

    /**
     * @return list<object>
     */
    public static function uniqueTimeline(iterable $events): array
    {
        $seen = [];
        $unique = [];
        foreach (collect($events)->where('event_type', '!=', 'trade_viewed')->sortBy('created_at') as $event) {
            $type = (string) $event->event_type;
            if ($type === '' || isset($seen[$type])) {
                continue;
            }
            $seen[$type] = true;
            $unique[] = $event;
        }

        return $unique;
    }

    /**
     * @return list<array{key: string, tone: string, label: string, done: bool, at: mixed, detail: string, waiting: string, actor_name: string, actor_avatar: string}>
     */
    public static function milestoneTimeline(TradeListing $listing): array
    {
        $events = collect($listing->events)->sortBy('created_at')->values();
        $first = static function (string ...$types) use ($events) {
            return $events->first(fn ($event) => in_array((string) $event->event_type, $types, true));
        };
        $actor = static function ($event, ?TradeUser $fallback): array {
            $who = $event?->actor ?? $fallback;

            return [
                'name' => $who?->display_name ?: $who?->username ?: 'Trader',
                'avatar' => (string) ($who?->avatar_url ?? ''),
            ];
        };

        $postedEvent = $first('trade_posted');
        $joinedEvent = $first('join_accepted');
        $pendingEvent = $first('trade_pending');
        $doneEvent = $first('trade_completed', 'trade_failed', 'trade_cancelled', 'trade_disputed');
        $status = (string) $listing->status;
        $joined = (bool) ($listing->counterparty_user_id || $joinedEvent);
        $pending = $listing->isPendingLike()
            || in_array($status, [
                TradeListing::STATUS_COMPLETED,
                TradeListing::STATUS_FAILED,
                TradeListing::STATUS_CANCELLED,
                TradeListing::STATUS_DISPUTED,
            ], true)
            || (bool) $pendingEvent;
        $done = in_array($status, [
            TradeListing::STATUS_COMPLETED,
            TradeListing::STATUS_FAILED,
            TradeListing::STATUS_CANCELLED,
            TradeListing::STATUS_DISPUTED,
        ], true);
        $doneLabel = 'Trade completed';
        $doneTone = 'done';
        $doneDetail = 'Both sides confirmed the trade';
        if ($done) {
            [$doneLabel, $doneTone, $doneDetail] = match ($status) {
                TradeListing::STATUS_FAILED => ['Trade failed', 'danger', 'The trade was marked failed'],
                TradeListing::STATUS_CANCELLED => ['Trade cancelled', 'danger', 'This trade was cancelled'],
                TradeListing::STATUS_DISPUTED => ['Trade disputed', 'danger', 'This trade is disputed'],
                default => ['Trade completed', 'done', 'Both sides confirmed the trade'],
            };
        }

        $postedActor = $actor($postedEvent, $listing->owner);
        $joinedActor = $actor(null, $listing->counterparty);

        $steps = [
            [
                'key' => 'posted',
                'tone' => 'posted',
                'label' => 'Trade posted',
                'done' => true,
                'at' => $postedEvent?->created_at ?? $listing->created_at,
                'detail' => 'created this trade listing',
                'waiting' => '',
                'actor_name' => $postedActor['name'],
                'actor_avatar' => $postedActor['avatar'],
            ],
            [
                'key' => 'joined',
                'tone' => 'joined',
                'label' => 'Trade joined',
                'done' => $joined,
                'at' => $joined ? ($joinedEvent?->created_at ?? $listing->accepted_at) : null,
                'detail' => 'accepted the trade',
                'waiting' => 'Waiting for someone to accept this trade...',
                'actor_name' => $joinedActor['name'],
                'actor_avatar' => $joinedActor['avatar'],
            ],
            [
                'key' => 'pending',
                'tone' => 'pending',
                'label' => 'Trade pending',
                'done' => $pending,
                'at' => $pending ? ($pendingEvent?->created_at ?? $joinedEvent?->created_at ?? $listing->accepted_at) : null,
                'detail' => 'The trade is in progress',
                'waiting' => 'Waiting for the trade to start...',
                'actor_name' => '',
                'actor_avatar' => '',
            ],
            [
                'key' => 'done',
                'tone' => $doneTone,
                'label' => $doneLabel,
                'done' => $done,
                'at' => $done ? ($doneEvent?->created_at ?? $listing->updated_at) : null,
                'detail' => $doneDetail,
                'waiting' => 'Waiting for both sides to confirm...',
                'actor_name' => '',
                'actor_avatar' => '',
            ],
        ];

        return array_values(array_reverse($steps));
    }

    public static function oauthUnavailableCopy(): string
    {
        if (app()->isLocal()) {
            return 'Configure Roblox OAuth. Set ROBLOX_CLIENT_ID, ROBLOX_CLIENT_SECRET, and ROBLOX_REDIRECT_URI in .env.';
        }

        return 'Under development.';
    }

    /**
     * @param  array<string, scalar>  $query
     */
    public static function wwwUrl(string $path, array $query = []): string
    {
        $url = rtrim(SabHost::origin('www'), '/').'/'.ltrim($path, '/');
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
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
                'label' => 'Create Trade Ad',
                'icon' => 'post',
                'active' => $path === TradePaths::create(),
            ],
            [
                'href' => TradePaths::marketplace(),
                'label' => 'View Trade Ads',
                'icon' => 'list',
                'active' => $path === TradePaths::marketplace(),
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
    /**
     * @return array{profile_id: string, username: ?string, display_name: ?string, avatar_url: ?string, profile_path: ?string, can_post: bool}|null
     */
    public static function userNav(?TradeUser $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'profile_id' => $user->profile_id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'avatar_url' => $user->avatar_url,
            'profile_path' => TradeProfileAccess::canAccessProfile($user) ? $user->profilePath() : null,
            'can_post' => $user->canPost(),
        ];
    }

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
            'is_hot' => $listing->is_hot,
            'is_top' => $listing->is_top,
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
            $payload['timeline'] = collect(self::uniqueTimeline($listing->events))
                ->map(fn ($event) => [
                    'type' => $event->event_type,
                    'label' => self::eventLabel((string) $event->event_type),
                    'at' => optional($event->created_at)->toIso8601String(),
                    'actor' => self::userPublic($event->actor),
                ])->values()
                ->all();
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

    /**
     * @return array<string, mixed>
     */
    public static function threadMessage(TradeNotification $row, TradeUser $viewer): array
    {
        return [
            'id' => $row->id,
            'message' => $row->message,
            'mine' => (int) $row->actor_user_id === (int) $viewer->id,
            'created_at' => optional($row->created_at)->toIso8601String(),
            'actor' => self::userPublic($row->actor),
        ];
    }
}
