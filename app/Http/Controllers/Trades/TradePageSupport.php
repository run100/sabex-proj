<?php

namespace App\Http\Controllers\Trades;

use App\Support\TradeCanonical;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    /**
     * @param  array<string, list<mixed>>  $rules
     * @return array<string, mixed>
     */
    protected function validatedQuery(Request $request, array $rules): array
    {
        $validator = Validator::make($request->query(), $rules);
        abort_unless($validator->passes(), 404);

        return $validator->validated();
    }
}
