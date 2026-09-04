<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabRenderService;
use App\Support\SabHost;
use Illuminate\View\View;

class PostController extends Controller
{
    use TradePageSupport;

    public function create(SabRenderService $sabRender): View
    {
        $context = array_merge($sabRender->calculatorViewContext(), $this->page([
            'tradePostMode' => true,
            'tradePublishUrl' => '/api/v1/trades',
            'canonical' => SabHost::origin('trades').'/post',
            'robots' => 'noindex,nofollow',
            'seoTitle' => 'Post a SAB trade',
            'seoDescription' => 'Build a Steal a Brainrot trade like the calculator, then publish it for other players.',
        ]));

        return view('seo.sab.calculator', $context);
    }
}
