<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeListingService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradeQueryRules;
use App\Support\TradeSeo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendingController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeListingService $listings): View
    {
        $this->requireSchema();
        $filters = $this->validatedQuery($request, TradeQueryRules::pendingListings());
        $page = $listings->pending($filters);
        $this->assertListPage($request, $page);
        $seo = TradeCanonical::forList(TradePaths::pending(), $request);

        return view('trades.pending', $this->page([
            'seoTitle' => 'Pending Steal a Brainrot Trades | SABExistCount',
            'seoDescription' => 'Browse pending Steal a Brainrot trades waiting to be finished in Roblox on SABExistCount.',
            'canonical' => $seo['canonical'],
            'robots' => $seo['robots'],
            'jsonLd' => TradeSeo::breadcrumbJsonLd('Pending Steal a Brainrot Trades', $seo['canonical']),
            'listings' => $page,
        ]));
    }
}
