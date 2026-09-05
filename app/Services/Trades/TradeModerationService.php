<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeListing;
use App\Models\TradeReport;
use App\Models\TradeUser;
use App\Models\TradeUserBlock;

class TradeModerationService
{
    public function __construct(
        private readonly TradeListingService $listings,
    ) {}

    public function report(TradeUser $reporter, array $data): TradeReport
    {
        if (! $reporter->isActive()) {
            throw TradeException::banned();
        }
        $daily = TradeReport::query()
            ->where('reporter_user_id', $reporter->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($daily >= (int) config('sab-trades.report_limit_per_day', 20)) {
            throw TradeException::rateLimited();
        }

        $listing = null;
        if (! empty($data['listing_public_id'])) {
            $listing = $this->listings->findPublic((string) $data['listing_public_id']);
        }
        $reported = null;
        if (! empty($data['profile_id'])) {
            $reported = TradeUser::findPublic((string) $data['profile_id']);
        } elseif ($listing) {
            $reported = $listing->owner;
        }
        if ($listing === null && $reported === null) {
            throw TradeException::invalid('TRADE_NOT_FOUND', 'Choose a trade or user to report.');
        }

        return TradeReport::query()->create([
            'reporter_user_id' => $reporter->id,
            'listing_id' => $listing?->id,
            'reported_user_id' => $reported?->id,
            'reason' => $data['reason'],
            'description' => isset($data['description']) ? mb_substr(strip_tags((string) $data['description']), 0, 1000) : null,
        ]);
    }

    public function review(TradeUser $moderator, TradeReport $report, string $status, ?string $note = null): TradeReport
    {
        $this->assertModerator($moderator);
        $report->status = $status;
        $report->resolution_note = $note;
        $report->reviewed_by = $moderator->id;
        $report->reviewed_at = now();
        $report->save();
        if ($report->listing) {
            $this->listings->event($report->listing, 'report_created', $moderator, ['report' => $report->public_id, 'status' => $status]);
        }

        return $report;
    }

    public function suspend(TradeUser $moderator, TradeUser $target): TradeUser
    {
        $this->assertModerator($moderator);
        $target->account_status = TradeUser::STATUS_SUSPENDED;
        $target->save();

        return $target;
    }

    public function block(TradeUser $user, TradeUser $target): TradeUserBlock
    {
        if ((int) $user->id === (int) $target->id) {
            throw TradeException::invalid('USER_BLOCKED', 'You cannot block yourself.');
        }

        return TradeUserBlock::query()->firstOrCreate(
            ['user_id' => $user->id, 'blocked_user_id' => $target->id],
            []
        );
    }

    public function unblock(TradeUser $user, TradeUser $target): void
    {
        TradeUserBlock::query()
            ->where('user_id', $user->id)
            ->where('blocked_user_id', $target->id)
            ->delete();
    }

    public function isModerator(TradeUser $user): bool
    {
        $subs = config('sab-trades.moderator_roblox_subs', []);

        return in_array($user->roblox_sub, is_array($subs) ? $subs : [], true);
    }

    private function assertModerator(TradeUser $user): void
    {
        if (! $this->isModerator($user)) {
            throw TradeException::forbidden('AUTH_REQUIRED', 'Moderator only.');
        }
    }
}
