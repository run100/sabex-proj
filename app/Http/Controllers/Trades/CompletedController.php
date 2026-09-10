<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeListingService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradeQueryRules;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompletedController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeListingService $listings): View
    {
        $this->requireSchema();
        $filters = $this->validatedQuery($request, TradeQueryRules::completedListings());
        $page = $listings->completed($filters);
        $this->assertListPage($request, $page);
        $seo = TradeCanonical::forList(TradePaths::completed(), $request);

        return view('trades.completed', $this->page([
            'seoTitle' => 'Completed Steal a Brainrot Trades & Trade History | SABExistCount',
            'seoDescription' => 'Verified Steal a Brainrot trades confirmed by both players on SABExistCount. Community reference only.',
            'canonical' => $seo['canonical'],
            'robots' => $seo['robots'],
            'listings' => $page,
        ]));
    }
}
