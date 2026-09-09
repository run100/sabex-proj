<?php

namespace App\Console\Commands;

use App\Models\SeoItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CheckConnectionsCommand extends Command
{
    private const REDIS_TTL_SECONDS = 60;

    protected $signature = 'app:check-connections';

    protected $description = 'Check the production database and Redis read/write connectivity';

    public function handle(): int
    {
        $this->line('Environment: '.app()->environment());

        $databaseOk = $this->checkDatabase();
        $redisOk = $this->checkRedis();

        if ($databaseOk && $redisOk) {
            $this->info('All connection checks passed.');

            return self::SUCCESS;
        }

        $this->error('One or more connection checks failed.');

        return self::FAILURE;
    }

    private function checkDatabase(): bool
    {
        $connectionName = (string) (config('database.default') ?: 'unknown');

        try {
            $items = SeoItem::query()
                ->orderBy('id')
                ->limit(100)
                ->get();

            $this->info(sprintf(
                'Database [%s]: OK; seo_items rows read: %d',
                $connectionName,
                $items->count(),
            ));

            if ($items->isNotEmpty()) {
                $this->table(
                    ['id', 'slug', 'name', 'rarity', 'is_listed', 'is_publish_html', 'total_exists', 'updated_at'],
                    $items->map(fn (SeoItem $item): array => [
                        'id' => (string) $item->id,
                        'slug' => (string) $item->slug,
                        'name' => (string) ($item->name ?? ''),
                        'rarity' => (string) ($item->rarity ?? ''),
                        'is_listed' => $item->is_listed ? 'yes' : 'no',
                        'is_publish_html' => $item->is_publish_html ? 'yes' : 'no',
                        'total_exists' => $item->total_exists === null ? '' : (string) $item->total_exists,
                        'updated_at' => $item->updated_at?->toDateTimeString() ?? '',
                    ])->all(),
                );
            }

            return true;
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Database [%s]: FAILED (%s).',
                $connectionName,
                class_basename($exception),
            ));

            return false;
        }
    }

    private function checkRedis(): bool
    {
        $connectionName = (string) (config('cache.stores.redis.connection') ?: 'cache');
        $key = 'connection-check:'.Str::uuid();
        $value = 'connection-check-value:'.Str::uuid();

        try {
            $redis = Redis::connection($connectionName);
            $written = $redis->setex($key, self::REDIS_TTL_SECONDS, $value);

            if ($written === false || $written === null) {
                throw new RuntimeException('Redis SETEX returned an unsuccessful result.');
            }

            if ($redis->get($key) !== $value) {
                throw new RuntimeException('Redis GET returned an unexpected value.');
            }

            $ttl = $redis->ttl($key);
            if (! is_numeric($ttl) || (int) $ttl <= 0 || (int) $ttl > self::REDIS_TTL_SECONDS) {
                throw new RuntimeException('Redis TTL is not within the expected range.');
            }

            $this->info(sprintf(
                'Redis [%s]: OK; temporary key TTL: %ds',
                $connectionName,
                (int) $ttl,
            ));

            return true;
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Redis [%s]: FAILED (%s).',
                $connectionName,
                class_basename($exception),
            ));

            return false;
        }
    }
}
