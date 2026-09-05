<?php

namespace App\Http\Controllers\Trades;

use App\Support\TradeCanonical;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

trait TradePageSupport
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function page(array $extra): array
    {
        return TradePresenter::page($extra);
    }

    protected function requireSchema(): void
    {
        abort_unless(TradeSchema::ready(), 404);
    }

    protected function assertListPage(Request $request, LengthAwarePaginator $page): void
    {
        TradeCanonical::abortInvalidPage($request, $page);
    }
}
