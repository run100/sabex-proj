<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeActivityService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeActivityService $activity): View
    {
        $this->requireSchema();
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'pending', 'completed', 'failed'], true)) {
            $status = 'all';
        }
        $user = auth('trades')->user();

        return view('trades.activity', $this->page([
            'seoTitle' => 'SABExistCount - Steal a Brainrot Trade Activity & Recent Trades',
            'seoDescription' => 'Track recent Steal a Brainrot trade activity on SABExistCount. Browse posted, joined, pending, completed and failed SAB trades, or filter activity by Roblox user.',
            'canonical' => TradeCanonical::absolute(TradePaths::activity()),
            'robots' => 'noindex,nofollow',
            'status' => $status,
            'counts' => $activity->countsForUser($user),
            'listings' => $activity->forUser($user, $status, (int) $request->query('page', 1)),
        ]));
    }
}
