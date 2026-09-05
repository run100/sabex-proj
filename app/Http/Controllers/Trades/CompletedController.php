<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeListingService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompletedController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeListingService $listings): View
    {
        $this->requireSchema();
        $page = $listings->completed($request->only(['page', 'limit', 'username', 'roblox_sub', 'brainrot_id', 'sort']));
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
