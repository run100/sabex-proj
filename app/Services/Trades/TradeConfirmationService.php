<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeConfirmation;
use App\Models\TradeListing;
use App\Models\TradeUser;
use Illuminate\Support\Facades\DB;

class TradeConfirmationService
{
    public function __construct(
        private readonly TradeListingService $listings,
        private readonly TradeNotificationService $notifications,
    ) {}

    public function confirm(TradeUser $user, TradeListing $listing, string $confirmation, ?string $note = null): TradeListing
    {
        if (! $user->isActive()) {
            throw TradeException::banned();
        }
        if (! in_array($confirmation, [TradeConfirmation::COMPLETED, TradeConfirmation::FAILED], true)) {
            throw TradeException::invalid('ONLY_PARTICIPANT_CAN_CONFIRM', 'Confirmation must be completed or failed.');
        }
        if (! $listing->isPendingLike()) {
            throw TradeException::conflict('TRADE_ALREADY_COMPLETED', 'This trade is not waiting for confirmation.');
        }
        $participantIds = [(int) $listing->owner_user_id, (int) $listing->counterparty_user_id];
        if (! in_array((int) $user->id, $participantIds, true)) {
            throw TradeException::forbidden('ONLY_PARTICIPANT_CAN_CONFIRM', 'Only the two traders can confirm.');
        }

        return DB::transaction(function () use ($user, $listing, $confirmation, $note): TradeListing {
            $locked = TradeListing::query()->where('id', $listing->id)->lockForUpdate()->first();
            if ($locked === null || ! $locked->isPendingLike()) {
                throw TradeException::conflict('TRADE_ALREADY_COMPLETED', 'This trade is not waiting for confirmation.');
            }

            TradeConfirmation::query()->updateOrCreate(
                ['listing_id' => $locked->id, 'user_id' => $user->id],
                ['confirmation' => $confirmation, 'note' => $note]
            );

            $this->listings->event(
                $locked,
                $confirmation === TradeConfirmation::COMPLETED ? 'confirmation_completed' : 'confirmation_failed',
                $user
            );

            $other = (int) $user->id === (int) $locked->owner_user_id ? $locked->counterparty : $locked->owner;
            if ($other) {
                $this->notifications->notify(
                    $other,
                    'other_user_confirmed',
                    'The other player confirmed',
                    ($user->display_name ?: $user->username).' marked this trade '.$confirmation.'.',
                    $locked,
                    null,
                    $user,
                );
            }

            $this->applyOutcome($locked);

            return $locked->fresh(['owner', 'counterparty', 'items.traits', 'confirmations']) ?? $locked;
        });
    }

    private function applyOutcome(TradeListing $listing): void
    {
        $rows = TradeConfirmation::query()->where('listing_id', $listing->id)->get()->keyBy('user_id');
        $owner = $rows->get($listing->owner_user_id);
        $counter = $listing->counterparty_user_id ? $rows->get($listing->counterparty_user_id) : null;
        if ($owner === null || $counter === null) {
            $listing->status = TradeListing::STATUS_PENDING_CONFIRMATION;
            $listing->save();

            return;
        }

        $a = $owner->confirmation;
        $b = $counter->confirmation;
        if ($a === TradeConfirmation::COMPLETED && $b === TradeConfirmation::COMPLETED) {
            $listing->status = TradeListing::STATUS_COMPLETED;
            $listing->completed_at = now();
            $listing->save();
            $this->listings->event($listing, 'trade_completed');
            $this->notifyBoth($listing, 'trade_completed', 'Trade completed', 'Both players confirmed the in-game trade.');

            return;
        }
        if ($a === TradeConfirmation::FAILED && $b === TradeConfirmation::FAILED) {
            $listing->status = TradeListing::STATUS_FAILED;
            $listing->failed_at = now();
            $listing->save();
            $this->listings->event($listing, 'trade_failed');
            $this->notifyBoth($listing, 'trade_failed', 'Trade failed', 'Both players marked the trade as failed.');

            return;
        }

        $listing->status = TradeListing::STATUS_DISPUTED;
        $listing->save();
        $this->listings->event($listing, 'trade_disputed');
        $this->notifyBoth($listing, 'trade_disputed', 'Trade disputed', 'The two confirmations do not match.');
    }

    private function notifyBoth(TradeListing $listing, string $type, string $title, string $message): void
    {
        foreach ([$listing->owner, $listing->counterparty] as $user) {
            if ($user) {
                $this->notifications->notify($user, $type, $title, $message, $listing);
            }
        }
    }
}
