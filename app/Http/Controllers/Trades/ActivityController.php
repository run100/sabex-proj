<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeActivityService;
use App\Support\SabHost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeActivityService $activity): View
    {
        $this->requireSchema();
        $status = (string) $request->query('status', 'all');

        return view('trades.activity', $this->page([
            'seoTitle' => 'Trade activity',
            'seoDescription' => 'Your open, pending, and completed Steal a Brainrot trades.',
            'canonical' => SabHost::origin('trades').'/activity',
            'robots' => 'noindex,nofollow',
            'status' => $status,
            'listings' => $activity->forUser(auth('trades')->user(), $status, (int) $request->query('page', 1)),
        ]));
    }
}
