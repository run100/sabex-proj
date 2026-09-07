<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeListingService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendingController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeListingService $listings): View
    {
        $this->requireSchema();
        $page = $listings->pending($request->only(['page', 'limit', 'sort']));
        $this->assertListPage($request, $page);
        $seo = TradeCanonical::forList(TradePaths::pending(), $request);

        return view('trades.pending', $this->page([
            'seoTitle' => 'Pending Steal a Brainrot Trades | SABExistCount',
            'seoDescription' => 'Browse pending Steal a Brainrot trades waiting to be finished in Roblox on SABExistCount.',
            'canonical' => $seo['canonical'],
            'robots' => $seo['robots'],
            'listings' => $page,
        ]));
    }
}
