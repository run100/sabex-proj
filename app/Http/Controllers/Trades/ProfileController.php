<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Services\Trades\TradeStatsService;
use App\Support\TradeCanonical;
use App\Support\TradeIndexEligibility;
use App\Support\TradeProfileAccess;
use App\Support\TradeSeo;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use TradePageSupport;

    public function __invoke(string $profile_id, TradeStatsService $stats): View
    {
        $this->requireSchema();
        $profile = TradeUser::findPublic($profile_id);
        abort_unless(TradeProfileAccess::canAccessProfile($profile), 404);

        $active = TradeListing::query()
            ->with(['owner', 'items.traits'])
            ->where('owner_user_id', $profile->id)
            ->where('status', TradeListing::STATUS_OPEN)
            ->whereHas('owner', fn ($q) => TradeProfileAccess::constrainPublicIdentity($q))
            ->orderByDesc('id')
            ->limit(20)
            ->get();
        $completed = TradeListing::query()
            ->with(['owner', 'counterparty', 'items.traits'])
            ->where('status', TradeListing::STATUS_COMPLETED)
            ->where(function ($q) use ($profile): void {
                $q->where('owner_user_id', $profile->id)->orWhere('counterparty_user_id', $profile->id);
            })
            ->whereHas('owner', fn ($q) => TradeProfileAccess::constrainPublicIdentity($q))
            ->where(function ($q): void {
                $q->whereNull('counterparty_user_id')
                    ->orWhereHas('counterparty', fn ($inner) => TradeProfileAccess::constrainPublicIdentity($inner));
            })
            ->orderByDesc('completed_at')
            ->limit(20)
            ->get();

        $name = $profile->display_name ?: $profile->username;
        $indexable = TradeIndexEligibility::isEligible($profile);

        return view('trades.profile', $this->page([
            'seoTitle' => $name.' Steal a Brainrot Trades & Trade History | SABExistCount',
            'seoDescription' => 'View '.$name.'\'s Steal a Brainrot trading profile, active SAB trades and completed trade history on SABExistCount.',
            'canonical' => TradeCanonical::absolute($profile->profilePath()),
            'jsonLd' => TradeSeo::breadcrumbJsonLd($name, TradeCanonical::absolute($profile->profilePath())),
            'robots' => $indexable ? 'index,follow' : 'noindex,follow',
            'profile' => $profile,
            'stats' => $stats->forUser($profile),
            'activeListings' => $active,
            'completedListings' => $completed,
        ]));
    }
}
