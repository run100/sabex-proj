<?php

namespace App\Services\Seo;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class SabPriceHistoryWriter
{
    /**
     * Merge today's variant points into an existing per-item file.
     * Existing dates are kept; the same calendar day is overwritten.
     *
     * @param  array<int|string, float|int|string>  $variantValues
     * @return array{slug: string, variants: array<string, list<array{date: string, value: float}>>}
     */
    public function mergeToday(string $slug, array $variantValues, ?string $date = null): array
    {
        $slug = $this->safeSlug($slug);
        if ($slug === null) {
            throw new \InvalidArgumentException('Invalid price-history slug.');
        }

        $date ??= Carbon::now()->toDateString();
        $payload = $this->read($slug);

        foreach ($variantValues as $variantId => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $key = (string) (int) $variantId;
            if ($key === '0') {
                continue;
            }
            $series = $payload['variants'][$key] ?? [];
            $payload['variants'][$key] = $this->upsertDay($series, $date, round((float) $value, 4));
        }

        $this->write($slug, $payload);

        return $payload;
    }

    /**
     * @param  list<array{date: string, value: float}>  $points
     * @return list<array{date: string, value: float}>
     */
    public static function filterSince(array $points, string $sinceDate): array
    {
        $kept = [];
        foreach ($points as $point) {
            if (! is_array($point) || ! isset($point['date'], $point['value'])) {
                continue;
            }
            $date = (string) $point['date'];
            if ($date < $sinceDate) {
                continue;
            }
            $kept[] = [
                'date' => $date,
                'value' => round((float) $point['value'], 4),
            ];
        }

        return $kept;
    }

    public function directory(): string
    {
        return storage_path('app/seo/sab-price-history');
    }

    /**
     * @return array{slug: string, variants: array<string, list<array{date: string, value: float}>>}
     */
    private function read(string $slug): array
    {
        $path = $this->directory().'/'.$slug.'.json';
        if (! File::isFile($path)) {
            return ['slug' => $slug, 'variants' => []];
        }

        $decoded = json_decode((string) File::get($path), true);
        $variants = is_array($decoded) ? ($decoded['variants'] ?? []) : [];
        if (! is_array($variants)) {
            $variants = [];
        }

        return ['slug' => $slug, 'variants' => $variants];
    }

    /**
     * @param  array{slug: string, variants: array<string, list<array{date: string, value: float}>>}  $payload
     */
    private function write(string $slug, array $payload): void
    {
        $dir = $this->directory();
        if (! File::isDirectory($dir)) {
            File::ensureDirectoryExists($dir);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException("Failed to encode {$slug}.json");
        }

        if (File::put($dir.'/'.$slug.'.json', $json) === false) {
            throw new \RuntimeException("Cannot write {$slug}.json");
        }
    }

    /**
     * @param  list<array{date: string, value: float}>  $series
     * @return list<array{date: string, value: float}>
     */
    private function upsertDay(array $series, string $date, float $value): array
    {
        $replaced = false;
        $next = [];
        foreach ($series as $point) {
            if (! is_array($point) || ! isset($point['date'], $point['value'])) {
                continue;
            }
            $pointDate = (string) $point['date'];
            if ($pointDate === $date) {
                $next[] = ['date' => $date, 'value' => $value];
                $replaced = true;
                continue;
            }
            $next[] = [
                'date' => $pointDate,
                'value' => round((float) $point['value'], 4),
            ];
        }

        if (! $replaced) {
            $next[] = ['date' => $date, 'value' => $value];
        }

        usort($next, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        return array_values($next);
    }

    private function safeSlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || ! preg_match('/^[a-z0-9][a-z0-9\-]*$/', $slug)) {
            return null;
        }

        return $slug;
    }
}
