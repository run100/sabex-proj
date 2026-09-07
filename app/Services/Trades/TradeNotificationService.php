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
    public const TYPE_MESSAGE = 'trade_message';

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

    /**
     * @return \Illuminate\Support\Collection<int, TradeNotification>
     */
    public function thread(TradeUser $user, TradeListing $listing)
    {
        return TradeNotification::query()
            ->with('actor')
            ->where('listing_id', $listing->id)
            ->where('type', self::TYPE_MESSAGE)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)->orWhere('actor_user_id', $user->id);
            })
            ->orderBy('id')
            ->get();
    }

    public function send(TradeUser $actor, TradeListing $listing, string $message): TradeNotification
    {
        $message = trim($message);
        if ($message === '' || mb_strlen($message) > 280) {
            throw TradeException::invalid('INVALID_MESSAGE', 'Enter a message up to 280 characters.');
        }
        $recipient = $this->recipient($actor, $listing);
        if ((int) $recipient->id === (int) $actor->id) {
            throw TradeException::invalid('INVALID_MESSAGE', 'You cannot message yourself.');
        }

        return $this->notify($recipient, self::TYPE_MESSAGE, 'New trade message', $message, $listing, null, $actor);
    }

    private function recipient(TradeUser $actor, TradeListing $listing): TradeUser
    {
        if ((int) $actor->id !== (int) $listing->owner_user_id) {
            $owner = $listing->owner;
            if ($owner === null) {
                throw TradeException::notFound();
            }

            return $owner;
        }

        $last = TradeNotification::query()
            ->with('actor')
            ->where('listing_id', $listing->id)
            ->where('type', self::TYPE_MESSAGE)
            ->where('user_id', $actor->id)
            ->whereNotNull('actor_user_id')
            ->where('actor_user_id', '!=', $actor->id)
            ->orderByDesc('id')
            ->first();
        if ($last?->actor) {
            return $last->actor;
        }

        throw TradeException::invalid('INVALID_MESSAGE', 'No visitor to reply to yet.');
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
