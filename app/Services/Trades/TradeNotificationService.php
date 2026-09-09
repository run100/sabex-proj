<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeNotification;
use App\Models\TradeUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->with(['listing', 'actor'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($this->limit($limit), ['*'], 'page', $page);
    }

    /**
     * @return Collection<int, TradeNotification>
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
        if ($this->containsMarkup($message)) {
            throw TradeException::invalid('INVALID_MESSAGE', 'HTML/XML tags are not allowed.');
        }
        if (! $this->isNormalText($message)) {
            throw TradeException::invalid(
                'INVALID_MESSAGE',
                'Only normal text, numbers, spaces, line breaks, and common punctuation are allowed.'
            );
        }
        $recipient = $this->recipient($actor, $listing);
        if ((int) $recipient->id === (int) $actor->id) {
            throw TradeException::invalid('INVALID_MESSAGE', 'You cannot message yourself.');
        }

        return DB::transaction(function () use ($actor, $listing, $message, $recipient): TradeNotification {
            TradeUser::query()
                ->whereIn('id', [(int) $actor->id, (int) $recipient->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $quota = $this->messageQuota($actor, $recipient);
            if ($quota['remaining'] < 1) {
                throw TradeException::conflict(
                    'MESSAGE_LIMIT_REACHED',
                    'You can send at most '.$quota['limit'].' messages to this user.'
                );
            }

            return $this->notify($recipient, self::TYPE_MESSAGE, 'New trade message', $message, $listing, null, $actor);
        });
    }

    /**
     * @return array{limit: int, sent: int, remaining: int}
     */
    public function messageQuota(TradeUser $actor, TradeUser $recipient): array
    {
        $limit = max(0, (int) config('sab-trades.message_limit_per_user', 2));
        $sent = $this->outboundMessageQuery($actor, $recipient)->count();

        return [
            'limit' => $limit,
            'sent' => $sent,
            'remaining' => max(0, $limit - $sent),
        ];
    }

    public function peer(TradeUser $actor, TradeListing $listing): ?TradeUser
    {
        if ((int) $actor->id !== (int) $listing->owner_user_id) {
            return $listing->owner;
        }

        $counterparty = $listing->counterparty;
        if ($counterparty && (int) $counterparty->id !== (int) $actor->id) {
            return $counterparty;
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

        return $last?->actor;
    }

    private function recipient(TradeUser $actor, TradeListing $listing): TradeUser
    {
        $peer = $this->peer($actor, $listing);
        if ($peer === null) {
            if ((int) $actor->id !== (int) $listing->owner_user_id) {
                throw TradeException::notFound();
            }

            throw TradeException::invalid('INVALID_MESSAGE', 'No visitor to reply to yet.');
        }

        return $peer;
    }

    private function containsMarkup(string $message): bool
    {
        return preg_match(
            '/<\/?[A-Za-z_:][A-Za-z0-9:._-]*(?:\s+[^<>]*)?\s*\/?>|<!--[\s\S]*?(?:-->|$)|<![A-Za-z][^>]*>|<\?[A-Za-z][^>]*\?>|<\/?[A-Za-z_:][A-Za-z0-9:._-]*(?:\s+[^<>]*)?$|&(?:[A-Za-z][A-Za-z0-9]+|#\d+|#x[0-9A-F]+);/i',
            $message
        ) === 1;
    }

    private function isNormalText(string $message): bool
    {
        return preg_match(
            '/\A[\p{L}\p{M}\p{N}\p{Zs}\r\n.,!?;:\'"()\-_\/，。！？；：、（）「」『』【】《》〈〉…—–·]+\z/u',
            $message
        ) === 1;
    }

    private function outboundMessageQuery(TradeUser $actor, TradeUser $recipient)
    {
        return TradeNotification::query()
            ->where('type', self::TYPE_MESSAGE)
            ->where('actor_user_id', $actor->id)
            ->where('user_id', $recipient->id);
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
