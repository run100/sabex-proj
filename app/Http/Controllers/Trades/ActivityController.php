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
        $status = (string) $request->query('status', 'received');
        if (! in_array($status, ['ads', 'received', 'sent', 'pending', 'completed', 'expired'], true)) {
            $status = 'received';
        }
        $user = auth('trades')->user();
        $tabCopy = [
            'ads' => 'Trade ads you posted. Open one to manage it or wait for offers.',
            'received' => 'Accept or decline offers on your trade ads before a chat is opened.',
            'sent' => "Offers you sent on someone else's trade ad. Wait for them to accept, or open the listing to message.",
            'pending' => 'Trades in progress after an offer was accepted. Finish in Roblox, then both sides mark completed.',
            'completed' => 'Trades you finished after both sides marked completed.',
            'expired' => 'Trade ads that timed out before both sides finished. They are closed and cannot be accepted.',
        ];

        return view('trades.activity', $this->page([
            'seoTitle' => 'SABExistCount - Steal a Brainrot Offers',
            'seoDescription' => 'Review received, sent, and expired Steal a Brainrot offers on SABExistCount.',
            'canonical' => TradeCanonical::absolute(TradePaths::offers()),
            'robots' => 'noindex,nofollow',
            'status' => $status,
            'tabCopy' => $tabCopy,
            'counts' => $activity->countsForUser($user),
            'listings' => $activity->forUser($user, $status, (int) $request->query('page', 1)),
        ]));
    }
}
