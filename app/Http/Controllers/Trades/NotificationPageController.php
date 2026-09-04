<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeNotificationService;
use App\Support\SabHost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPageController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeNotificationService $notifications): View
    {
        $this->requireSchema();

        return view('trades.notifications', $this->page([
            'seoTitle' => 'Trade notifications',
            'seoDescription' => 'Join requests and trade status updates.',
            'canonical' => SabHost::origin('trades').'/notifications',
            'robots' => 'noindex,nofollow',
            'notifications' => $notifications->forUser(auth('trades')->user(), (int) $request->query('page', 1)),
        ]));
    }
}
