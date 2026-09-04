<?php

namespace Tests\Unit;

use App\Services\Seo\SabPriceHistoryWriter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SabPriceHistoryWriterTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('app/seo/sab-price-history');
        File::ensureDirectoryExists($this->dir);
    }

    public function test_merge_today_appends_and_keeps_old_points(): void
    {
        $slug = 'writer-keep-history';
        $path = $this->dir.'/'.$slug.'.json';
        File::put($path, json_encode([
            'slug' => $slug,
            'variants' => [
                '1001' => [
                    ['date' => '2026-01-01', 'value' => 10],
                    ['date' => '2026-07-22', 'value' => 99],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES));

        $payload = app(SabPriceHistoryWriter::class)->mergeToday($slug, [1001 => 80], '2026-08-21');

        $this->assertCount(3, $payload['variants']['1001']);
        $this->assertSame('2026-01-01', $payload['variants']['1001'][0]['date']);
        $this->assertSame(10.0, $payload['variants']['1001'][0]['value']);
        $this->assertSame('2026-08-21', $payload['variants']['1001'][2]['date']);
        $this->assertSame(80.0, $payload['variants']['1001'][2]['value']);

        File::delete($path);
    }

    public function test_merge_today_overwrites_same_day_without_dropping_history(): void
    {
        $slug = 'writer-overwrite-today';
        $path = $this->dir.'/'.$slug.'.json';
        File::put($path, json_encode([
            'slug' => $slug,
            'variants' => [
                '1001' => [
                    ['date' => '2026-01-01', 'value' => 10],
                    ['date' => '2026-08-21', 'value' => 70],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES));

        $payload = app(SabPriceHistoryWriter::class)->mergeToday($slug, [1001 => 88], '2026-08-21');

        $this->assertCount(2, $payload['variants']['1001']);
        $this->assertSame(['date' => '2026-01-01', 'value' => 10.0], $payload['variants']['1001'][0]);
        $this->assertSame(['date' => '2026-08-21', 'value' => 88.0], $payload['variants']['1001'][1]);

        File::delete($path);
    }

    public function test_filter_since_keeps_last_30_days(): void
    {
        $points = [
            ['date' => '2026-01-01', 'value' => 10],
            ['date' => '2026-07-22', 'value' => 99],
            ['date' => '2026-08-21', 'value' => 80],
        ];

        $kept = SabPriceHistoryWriter::filterSince($points, '2026-07-22');

        $this->assertCount(2, $kept);
        $this->assertSame('2026-07-22', $kept[0]['date']);
        $this->assertSame('2026-08-21', $kept[1]['date']);
    }
}
