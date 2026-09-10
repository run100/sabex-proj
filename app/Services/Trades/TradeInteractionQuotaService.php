<?php

namespace App\Services\Trades;

use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use Illuminate\Database\Eloquent\Builder;

class TradeInteractionQuotaService
{
    /**
     * @return array{limit: int, sent: int, remaining: int}
     */
    public function contactQuota(TradeListing $listing, TradeUser $actor, TradeUser $recipient): array
    {
        $limit = $this->limit();
        $sent = $this->messageCount($listing, $actor, $recipient)
            + $this->joinCount($listing, $actor, $recipient);

        return [
            'limit' => $limit,
            'sent' => $sent,
            'remaining' => max(0, $limit - $sent),
        ];
    }

    /**
     * @return array{limit: int, sent: int, remaining: int}
     */
    public function messageQuota(TradeListing $listing, TradeUser $actor, TradeUser $recipient): array
    {
        $limit = $this->limit();
        $sent = $this->messageCount($listing, $actor, $recipient);

        return [
            'limit' => $limit,
            'sent' => $sent,
            'remaining' => max(0, $limit - $sent),
        ];
    }

    public function lockPair(TradeUser $actor, TradeUser $recipient): void
    {
        TradeUser::query()
            ->whereIn('id', [(int) $actor->id, (int) $recipient->id])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function limit(): int
    {
        return max(0, (int) config('sab-trades.contact_limit_per_user', 5));
    }

    private function messageCount(TradeListing $listing, TradeUser $actor, TradeUser $recipient): int
    {
        return $this->messageQuery($listing, $actor, $recipient)->count();
    }

    private function joinCount(TradeListing $listing, TradeUser $actor, TradeUser $recipient): int
    {
        return TradeJoinRequest::query()
            ->where('listing_id', $listing->id)
            ->where('requester_user_id', $actor->id)
            ->where('owner_user_id', $recipient->id)
            ->count();
    }

    /**
     * @return Builder<TradeNotification>
     */
    private function messageQuery(TradeListing $listing, TradeUser $actor, TradeUser $recipient): Builder
    {
        return TradeNotification::query()
            ->where('listing_id', $listing->id)
            ->where('type', TradeNotificationService::TYPE_MESSAGE)
            ->where('actor_user_id', $actor->id)
            ->where('user_id', $recipient->id);
    }
}
