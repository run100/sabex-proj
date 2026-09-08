<?php

namespace App\Http\Controllers\Trades;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\TradeJoinService;
use App\Services\Trades\TradeListingService;
use App\Services\Trades\TradeNotificationService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradePresenter;
use App\Support\TradeSeo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TradeShowController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, string $ulid, TradeListingService $listings, TradeJoinService $joins, TradeNotificationService $notifications): View
    {
        $this->requireSchema();
        try {
            $listing = $listings->findPublic($ulid);
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
        $messagePeer = $user ? $notifications->peer($user, $listing) : null;
        if ($user && (int) $user->id === (int) $listing->owner_user_id) {
            $joinRequests = $joins->forListing($user, $listing);
        }
        $seo = TradeSeo::listing($listing);

        return view('trades.show', $this->page([
            'seoTitle' => $seo['title'],
            'seoDescription' => $seo['description'],
            'canonical' => TradeCanonical::absolute(TradePaths::show($listing->public_id)),
            'robots' => 'index,follow',
            'listing' => $listing,
            'listingH1' => $seo['h1'],
            'h1Offering' => $seo['h1Offering'],
            'h1Looking' => $seo['h1Looking'],
            'offeringMore' => $seo['offeringMore'],
            'lookingMore' => $seo['lookingMore'],
            'card' => TradePresenter::listing($listing, true),
            'joinRequests' => $joinRequests,
            'messagePeer' => $messagePeer,
        ]));
    }
}
