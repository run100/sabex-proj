<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeListingService;
use App\Support\SabHost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompletedController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeListingService $listings): View
    {
        $this->requireSchema();

        return view('trades.completed', $this->page([
            'seoTitle' => 'Completed SAB trades',
            'seoDescription' => 'Verified completed Steal a Brainrot trades confirmed by both players.',
            'canonical' => SabHost::origin('trades').'/completed',
            'robots' => 'index,follow',
            'listings' => $listings->completed($request->only(['page', 'limit', 'username', 'roblox_sub', 'brainrot_id', 'sort'])),
        ]));
    }
}
