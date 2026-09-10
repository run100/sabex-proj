<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoItem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Stores the derived Wiki item catalog so public pages do not rebuild all
 * Eloquent relationships on every request.
 */
final class SabWikiCatalogService
{
    public const SCHEMA_VERSION = 1;

    public const PUBLIC_PATH = '/data/seo/sab-wiki-catalog.json';

    private const STORAGE_PATH = 'app/seo/sab-wiki-catalog.json';

    public static function path(): string
    {
        if (app()->environment('testing')) {
            return storage_path('framework/testing/sab-wiki-catalog.json');
        }

        return storage_path(self::STORAGE_PATH);
    }

    public static function publicUrl(): string
    {
        return self::PUBLIC_PATH;
    }

    /**
     * @return array{generated_at: string, url: string, rows: list<array<string, mixed>>, summary: array<string, mixed>}|null
     */
    public static function read(): ?array
    {
        $path = self::path();
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        try {
            $data = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('SAB Wiki catalog JSON is invalid; using database fallback.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! is_array($data)
            || (int) ($data['schema_version'] ?? 0) !== self::SCHEMA_VERSION
            || ! is_string($data['generated_at'] ?? null)
            || ! is_array($data['rows'] ?? null)
            || $data['rows'] === []
            || ! is_array($data['summary'] ?? null)) {
            Log::warning('SAB Wiki catalog JSON has an invalid schema; using database fallback.', [
                'path' => $path,
            ]);

            return null;
        }

        return [
            'generated_at' => $data['generated_at'],
            'url' => self::publicUrl(),
            'rows' => array_values(array_filter($data['rows'], 'is_array')),
            'summary' => $data['summary'],
        ];
    }

    /**
     * @return array{path: string, generated_at: string, rows: int, bytes: int}
     */
    public function publish(SeoGame $game, ?callable $onProgress = null): array
    {
        $items = SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->with([
                'variants' => fn ($query) => $query
                    ->whereIn('variant_type', ['base', 'mutation'])
                    ->with('currentValues.source'),
            ])
            ->get();

        $catalog = app(SabRenderService::class)->wikiCatalogForStorage($items);
        if ($catalog['rows'] === []) {
            throw new \RuntimeException('SAB Wiki catalog is empty; previous file was kept.');
        }

        $generatedAt = now()->toIso8601String();
        $payload = [
            'schema_version' => self::SCHEMA_VERSION,
            'generated_at' => $generatedAt,
            'rows' => $catalog['rows'],
            'summary' => $catalog['summary'],
        ];
        $path = self::path();
        File::ensureDirectoryExists(dirname($path));
        $temporaryPath = $path.'.tmp-'.Str::uuid();

        try {
            File::put($temporaryPath, json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ));
            if (! @rename($temporaryPath, $path)) {
                throw new \RuntimeException('Unable to publish SAB Wiki catalog.');
            }
        } catch (\Throwable $e) {
            if (is_file($temporaryPath)) {
                File::delete($temporaryPath);
            }

            throw $e;
        }

        if (is_callable($onProgress)) {
            $onProgress('SAB Wiki catalog published: rows='.count($catalog['rows']));
        }

        return [
            'path' => $path,
            'generated_at' => $generatedAt,
            'rows' => count($catalog['rows']),
            'bytes' => (int) (filesize($path) ?: 0),
        ];
    }
}
