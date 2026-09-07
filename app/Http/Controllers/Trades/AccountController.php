<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\TradeUser;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradePresenter;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(): View
    {
        /** @var TradeUser $user */
        $user = auth('trades')->user();

        return view('trades.account', TradePresenter::page([
            'seoTitle' => 'Your SABExistCount account',
            'seoDescription' => 'Manage your SABExistCount trading account.',
            'canonical' => TradeCanonical::absolute(TradePaths::account()),
            'robots' => 'noindex,nofollow',
            'accountUser' => $user,
        ]));
    }
}
