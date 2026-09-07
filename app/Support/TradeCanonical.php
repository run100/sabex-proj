<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class TradeCanonical
{
    /**
     * @return array{canonical: string, robots: string}
     */
    public static function forList(string $path, Request $request): array
    {
        $query = $request->query();
        $page = $query['page'] ?? null;
        unset($query['page']);
        $hasExtra = $query !== [];
        $pageNum = 1;
        if ($page !== null && $page !== '' && $page !== '1') {
            $pageNum = (int) $page;
        }
        $origin = rtrim(SabHost::origin('www'), '/');
        $canonical = $origin.$path;
        if (! $hasExtra && $pageNum > 1) {
            $canonical .= '?page='.$pageNum;
        }

        return [
            'canonical' => $canonical,
            'robots' => $hasExtra ? 'noindex,follow' : 'index,follow',
        ];
    }

    public static function abortInvalidPage(Request $request, LengthAwarePaginator $page): void
    {
        $raw = $request->query('page');
        if ($raw === null || $raw === '') {
            return;
        }
        if (! is_numeric($raw) || (string) (int) $raw !== (string) $raw || (int) $raw < 1) {
            abort(404);
        }
        $requested = (int) $raw;
        if ($requested > max(1, $page->lastPage())) {
            abort(404);
        }
    }

    public static function absolute(string $path): string
    {
        return rtrim(SabHost::origin('www'), '/').$path;
    }
}
