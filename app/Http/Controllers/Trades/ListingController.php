<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeListingService;
use App\Support\SabHost;
use App\Support\TradePresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeListingService $listings): View
    {
        $page = $this->page([
            'seoTitle' => 'SAB Trades — Steal a Brainrot player listings',
            'seoDescription' => 'Browse open Steal a Brainrot trades. Compare both sides, then finish the swap in Roblox.',
            'canonical' => SabHost::origin('trades').'/',
            'robots' => 'index,follow',
            'listings' => collect(),
        ]);
        if (! $page['schemaMissing']) {
            $page['listings'] = $listings->recent($request->only([
                'page', 'limit', 'want_brainrot_id', 'have_brainrot_id', 'min_value', 'max_value', 'sort',
            ]));
        }

        return view('trades.listings', $page);
    }
}
