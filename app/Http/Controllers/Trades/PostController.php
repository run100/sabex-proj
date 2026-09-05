<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabRenderService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use Illuminate\View\View;

class PostController extends Controller
{
    use TradePageSupport;

    public function create(SabRenderService $sabRender): View
    {
        $context = array_merge($sabRender->calculatorViewContext(), $this->page([
            'tradePostMode' => true,
            'tradePublishUrl' => TradePaths::apiTrades(),
            'canonical' => TradeCanonical::absolute(TradePaths::create()),
            'robots' => 'index,follow',
            'seoTitle' => 'Post a Steal a Brainrot Trade Ad | SABExistCount',
            'seoDescription' => 'Create a Steal a Brainrot trade ad on SABExistCount. Add the Brainrots you have, choose what you want, compare SAB values and W/F/L, then publish your trade.',
        ]));

        $context['calculatorCopy'] = array_merge($context['calculatorCopy'] ?? [], [
            'h1' => 'Post a Steal a Brainrot Trade',
            'intro' => 'Add the Brainrots you are offering and what you are looking for, then publish the listing for other players to join.',
        ]);
        $context['calculatorUi'] = array_merge($context['calculatorUi'] ?? [], [
            'offerTitle' => "You're Offering",
            'receiveTitle' => "You're Looking For",
            'compareNeedOffer' => "Add items to You're Offering to compare the trade.",
            'compareNeedReceive' => "Add items to You're Looking For to compare the trade.",
        ]);

        return view('seo.sab.calculator', $context);
    }
}
