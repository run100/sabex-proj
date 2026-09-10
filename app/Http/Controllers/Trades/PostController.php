<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabRenderService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradeSeo;
use Illuminate\View\View;

class PostController extends Controller
{
    use TradePageSupport;

    public function create(SabRenderService $sabRender): View
    {
        $context = array_merge($sabRender->tradeBuilderViewContext(), $this->page([
            'tradePublishUrl' => TradePaths::apiTrades(),
            'canonical' => TradeCanonical::absolute(TradePaths::create()),
            'robots' => 'index,follow',
            'seoTitle' => 'Post a Steal a Brainrot Trade Ad | SABExistCount',
            'seoDescription' => 'Post a Steal a Brainrot trade ad on SABExistCount. Add I Have and I Want items with mutations and traits, compare SAB values, income, exist counts, and W/F/L, then publish. Finish the swap in Roblox.',
            'seoKeywords' => 'Steal a Brainrot, trade ad, post trade, mutations, traits, SAB values, W/F/L',
            'maxItemsPerSide' => (int) config('sab-trades.max_items_per_side', 9),
            'jsonLd' => TradeSeo::breadcrumbJsonLd('Create Trade Ad', TradeCanonical::absolute(TradePaths::create())),
        ]));

        return view('trades.create', $context);
    }
}
