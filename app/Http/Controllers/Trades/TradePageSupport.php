<?php

namespace App\Http\Controllers\Trades;

use App\Support\TradePresenter;
use App\Support\TradeSchema;

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
}
