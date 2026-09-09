<?php

namespace App\Services\Trades;

use App\Models\TradeJoinRequest;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use Illuminate\Database\Eloquent\Builder;

class TradeInteractionQuotaService
{
    /**
     * @return array{limit: int, sent: int, remaining: int}
     */
    public function contactQuota(TradeUser $actor, TradeUser $recipient): array
    {
        $limit = $this->limit();
        $sent = $this->messageCount($actor, $recipient)
            + $this->joinCount($actor, $recipient);

        return [
            'limit' => $limit,
            'sent' => $sent,
            'remaining' => max(0, $limit - $sent),
        ];
    }

    /**
     * @return array{limit: int, sent: int, remaining: int}
     */
    public function messageQuota(TradeUser $actor, TradeUser $recipient): array
    {
        $limit = $this->limit();
        $sent = $this->messageCount($actor, $recipient);

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
        return max(0, (int) config('sab-trades.contact_limit_per_user', 2));
    }

    private function messageCount(TradeUser $actor, TradeUser $recipient): int
    {
        return $this->messageQuery($actor, $recipient)->count();
    }

    private function joinCount(TradeUser $actor, TradeUser $recipient): int
    {
        return TradeJoinRequest::query()
            ->where('requester_user_id', $actor->id)
            ->where('owner_user_id', $recipient->id)
            ->count();
    }

    /**
     * @return Builder<TradeNotification>
     */
    private function messageQuery(TradeUser $actor, TradeUser $recipient): Builder
    {
        return TradeNotification::query()
            ->where('type', TradeNotificationService::TYPE_MESSAGE)
            ->where('actor_user_id', $actor->id)
            ->where('user_id', $recipient->id);
    }
}
