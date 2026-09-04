<?php

namespace App\Http\Controllers\Trades;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\TradeJoinService;
use App\Services\Trades\TradeListingService;
use App\Support\SabHost;
use App\Support\TradePresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TradeShowController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, string $public_id, TradeListingService $listings, TradeJoinService $joins): View
    {
        $this->requireSchema();
        try {
            $listing = $listings->findPublic($public_id);
        } catch (TradeException) {
            abort(404);
        }
        $sessionKey = 'trade_viewed_'.$listing->public_id;
        if (! $request->session()->has($sessionKey)) {
            $listings->recordView($listing);
            $request->session()->put($sessionKey, true);
            $listing->refresh();
        }

        $user = auth('trades')->user();
        $joinRequests = [];
        if ($user && (int) $user->id === (int) $listing->owner_user_id) {
            $joinRequests = $joins->forListing($user, $listing);
        }

        return view('trades.show', $this->page([
            'seoTitle' => 'Trade '.$listing->public_id,
            'seoDescription' => 'Steal a Brainrot trade listing. Community reference only — finish the swap in Roblox.',
            'canonical' => SabHost::origin('trades').'/t/'.$listing->public_id,
            'robots' => 'noindex,follow',
            'listing' => $listing,
            'card' => TradePresenter::listing($listing, true),
            'joinRequests' => $joinRequests,
        ]));
    }
}
