<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TradeNotificationService
{
    public function notify(
        TradeUser $user,
        string $type,
        string $title,
        ?string $message = null,
        ?TradeListing $listing = null,
        ?TradeJoinRequest $join = null,
        ?TradeUser $actor = null,
    ): TradeNotification {
        return TradeNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'listing_id' => $listing?->id,
            'join_request_id' => $join?->id,
            'actor_user_id' => $actor?->id,
            'title' => $title,
            'message' => $message,
        ]);
    }

    public function forUser(TradeUser $user, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return TradeNotification::query()
            ->with('listing')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($this->limit($limit), ['*'], 'page', $page);
    }

    public function unreadCount(TradeUser $user): int
    {
        return TradeNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function markRead(TradeUser $user, int $id): TradeNotification
    {
        $row = TradeNotification::query()->where('user_id', $user->id)->where('id', $id)->first();
        if ($row === null) {
            throw TradeException::notFound('TRADE_NOT_FOUND', 'Notification not found.');
        }
        if (! $row->is_read) {
            $row->is_read = true;
            $row->read_at = now();
            $row->save();
        }

        return $row;
    }

    public function markAllRead(TradeUser $user): int
    {
        return TradeNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    private function limit(int $limit): int
    {
        return max(1, min($limit, 50));
    }
}
