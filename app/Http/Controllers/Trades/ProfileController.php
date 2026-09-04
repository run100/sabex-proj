<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Services\Trades\TradeStatsService;
use App\Support\SabHost;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use TradePageSupport;

    public function __invoke(string $roblox_sub, TradeStatsService $stats): View
    {
        $this->requireSchema();
        $profile = TradeUser::query()->where('roblox_sub', $roblox_sub)->first();
        abort_if($profile === null || $profile->account_status === TradeUser::STATUS_DELETED, 404);

        $active = TradeListing::query()
            ->with(['owner', 'items.traits'])
            ->where('owner_user_id', $profile->id)
            ->where('status', TradeListing::STATUS_OPEN)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
        $completed = TradeListing::query()
            ->with(['owner', 'counterparty', 'items.traits'])
            ->where('status', TradeListing::STATUS_COMPLETED)
            ->where(function ($q) use ($profile): void {
                $q->where('owner_user_id', $profile->id)->orWhere('counterparty_user_id', $profile->id);
            })
            ->orderByDesc('completed_at')
            ->limit(20)
            ->get();

        return view('trades.profile', $this->page([
            'seoTitle' => ($profile->display_name ?: $profile->username).' — SAB Trades',
            'seoDescription' => 'Public Steal a Brainrot trade profile.',
            'canonical' => SabHost::origin('trades').'/u/'.$profile->roblox_sub,
            'robots' => 'noindex,follow',
            'profile' => $profile,
            'stats' => $stats->forUser($profile),
            'activeListings' => $active,
            'completedListings' => $completed,
        ]));
    }
}
