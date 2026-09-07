<?php

namespace Tests\Concerns;

trait UsesTradeWww
{
    protected function tradeUrl(string $path = '/trading'): string
    {
        return 'http://www.sabex.lab'.$path;
    }
}
