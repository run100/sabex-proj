<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CheckConnectionsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('seo_items', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->string('name')->nullable();
            $table->string('rarity')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->boolean('is_publish_html')->default(true);
            $table->integer('total_exists')->nullable();
            $table->timestamps();
        });

        config(['cache.stores.redis.connection' => 'cache']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('seo_items');

        parent::tearDown();
    }

    public function test_reads_the_first_100_items_and_verifies_redis_write_read_and_ttl(): void
    {
        $this->seedItems(101);
        $this->mockSuccessfulRedis();

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower(ltrim((string) $query->sql));
        });

        $this->artisan('app:check-connections')
            ->expectsOutput('Environment: testing')
            ->expectsOutputToContain('Database [sqlite]: OK; seo_items rows read: 100')
            ->expectsOutputToContain('check-item-001')
            ->expectsOutputToContain('check-item-100')
            ->expectsOutputToContain('Redis [cache]: OK; temporary key TTL: 59s')
            ->expectsOutput('All connection checks passed.')
            ->assertSuccessful();

        $this->assertNotEmpty($queries);
        $this->assertTrue(collect($queries)->every(
            fn (string $sql): bool => str_starts_with($sql, 'select'),
        ));
    }

    public function test_database_failure_does_not_prevent_redis_check(): void
    {
        Schema::dropIfExists('seo_items');
        $this->mockSuccessfulRedis();

        $this->artisan('app:check-connections')
            ->expectsOutputToContain('Database [sqlite]: FAILED')
            ->expectsOutputToContain('Redis [cache]: OK')
            ->expectsOutput('One or more connection checks failed.')
            ->assertFailed();
    }

    public function test_setex_failure_returns_failure_without_reading_or_deleting_a_key(): void
    {
        $this->seedItems(1);
        $redis = Mockery::mock();
        $redis->shouldReceive('setex')->once()->andReturn(false);
        $redis->shouldNotReceive('get');
        $redis->shouldNotReceive('ttl');
        $redis->shouldNotReceive('del');
        $redis->shouldNotReceive('delete');
        Redis::shouldReceive('connection')->once()->with('cache')->andReturn($redis);

        $this->artisan('app:check-connections')
            ->expectsOutputToContain('Database [sqlite]: OK; seo_items rows read: 1')
            ->expectsOutputToContain('Redis [cache]: FAILED')
            ->assertFailed();
    }

    public function test_unexpected_value_returns_failure(): void
    {
        $this->seedItems(1);
        $redis = Mockery::mock();
        $redis->shouldReceive('setex')->once()->andReturn(true);
        $redis->shouldReceive('get')->once()->andReturn('unexpected-value');
        $redis->shouldNotReceive('ttl');
        $redis->shouldNotReceive('del');
        $redis->shouldNotReceive('delete');
        Redis::shouldReceive('connection')->once()->with('cache')->andReturn($redis);

        $this->artisan('app:check-connections')
            ->expectsOutputToContain('Redis [cache]: FAILED')
            ->assertFailed();
    }

    public function test_non_positive_ttl_returns_failure(): void
    {
        $this->seedItems(1);
        $value = null;
        $redis = Mockery::mock();
        $redis->shouldReceive('setex')->once()->withArgs(function ($key, $ttl, $candidateValue) use (&$value): bool {
            $value = $candidateValue;

            return str_starts_with((string) $key, 'connection-check:')
                && $ttl === 60;
        })->andReturn(true);
        $redis->shouldReceive('get')->once()->andReturnUsing(function () use (&$value) {
            return $value;
        });
        $redis->shouldReceive('ttl')->once()->andReturn(0);
        $redis->shouldNotReceive('del');
        $redis->shouldNotReceive('delete');
        Redis::shouldReceive('connection')->once()->with('cache')->andReturn($redis);

        $this->artisan('app:check-connections')
            ->expectsOutputToContain('Redis [cache]: FAILED')
            ->assertFailed();
    }

    public function test_failure_output_does_not_expose_connection_details(): void
    {
        $this->seedItems(1);
        $redis = Mockery::mock();
        $redis->shouldReceive('setex')->once()->andThrow(
            new RuntimeException('redis://user:super-secret@secret.example'),
        );
        $redis->shouldNotReceive('get');
        $redis->shouldNotReceive('ttl');
        $redis->shouldNotReceive('del');
        $redis->shouldNotReceive('delete');
        Redis::shouldReceive('connection')->once()->with('cache')->andReturn($redis);

        $this->artisan('app:check-connections')
            ->expectsOutputToContain('Redis [cache]: FAILED (RuntimeException).')
            ->doesntExpectOutputToContain('super-secret')
            ->doesntExpectOutputToContain('secret.example')
            ->assertFailed();
    }

    private function mockSuccessfulRedis(): void
    {
        $key = null;
        $value = null;
        $redis = Mockery::mock();
        $redis->shouldReceive('setex')->once()->withArgs(function ($candidateKey, $ttl, $candidateValue) use (&$key, &$value): bool {
            $key = (string) $candidateKey;
            $value = (string) $candidateValue;

            return str_starts_with($key, 'connection-check:')
                && $ttl === 60
                && $value !== '';
        })->andReturn(true);
        $redis->shouldReceive('get')->once()->withArgs(function ($candidateKey) use (&$key): bool {
            return $candidateKey === $key;
        })->andReturnUsing(function () use (&$value) {
            return $value;
        });
        $redis->shouldReceive('ttl')->once()->withArgs(function ($candidateKey) use (&$key): bool {
            return $candidateKey === $key;
        })->andReturn(59);
        $redis->shouldNotReceive('del');
        $redis->shouldNotReceive('delete');
        Redis::shouldReceive('connection')->once()->with('cache')->andReturn($redis);
    }

    private function seedItems(int $count): void
    {
        $timestamp = now();
        $rows = [];

        for ($id = 1; $id <= $count; $id++) {
            $rows[] = [
                'slug' => sprintf('check-item-%03d', $id),
                'name' => sprintf('Check Item %03d', $id),
                'rarity' => 'Common',
                'is_listed' => true,
                'is_publish_html' => true,
                'total_exists' => $id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('seo_items')->insert($rows);
    }
}
