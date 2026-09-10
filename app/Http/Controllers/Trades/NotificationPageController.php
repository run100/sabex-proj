<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeNotificationService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradeQueryRules;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPageController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeNotificationService $notifications): View
    {
        $this->requireSchema();
        $input = $this->validatedQuery($request, TradeQueryRules::notifications());

        return view('trades.notifications', $this->page([
            'seoTitle' => 'Trade notifications',
            'seoDescription' => 'Join requests and trade status updates.',
            'canonical' => TradeCanonical::absolute(TradePaths::notifications()),
            'robots' => 'noindex,nofollow',
            'notifications' => $notifications->forUser(auth('trades')->user(), $input['page'] ?? 1),
        ]));
    }
}
