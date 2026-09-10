<?php

namespace Tests\Feature;

use Tests\TestCase;

class MeOpenListingsCountWithoutSchemaTest extends TestCase
{
    public function test_me_returns_zero_open_count_when_trade_schema_is_missing(): void
    {
        config(['sab.hosts.www' => 'www.sabex.lab']);

        $this->getJson('http://www.sabex.lab/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user', null)
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.open_listings_count', 0);
    }
}
