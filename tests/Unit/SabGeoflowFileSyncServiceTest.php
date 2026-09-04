<?php

namespace Tests\Unit;

use App\Services\Seo\SabGeoflowFileSyncService;
use Tests\TestCase;

class SabGeoflowFileSyncServiceTest extends TestCase
{
    public function test_copies_only_when_checksum_changes_and_never_touches_price_history(): void
    {
        $root = storage_path('framework/testing/geoflow-sync-'.uniqid());
        $sourceDir = $root.'/backend/resources/seo/sab';
        $destDir = $root.'/dest';
        mkdir($sourceDir, 0775, true);
        mkdir($destDir, 0775, true);
        $source = $sourceDir.'/codes.json';
        $destination = $destDir.'/codes.json';
        file_put_contents($source, '{"verified_at":"2026-09-03"}');

        config(['sab.geoflow_root' => $root]);

        $service = new class($source, $destination) extends SabGeoflowFileSyncService
        {
            public function __construct(
                private string $testSource,
                private string $testDestination,
            ) {}

            protected function filePairs(): array
            {
                return [$this->testSource => $this->testDestination];
            }
        };

        $first = $service->syncFiles();
        $this->assertSame('copied', $first[0]['status']);
        $this->assertFileExists($destination);

        $second = $service->syncFiles();
        $this->assertSame('unchanged', $second[0]['status']);

        $dry = $service->syncFiles(true);
        $this->assertSame('unchanged', $dry[0]['status']);

        $encoded = json_encode(app(SabGeoflowFileSyncService::class)->syncFiles(true));
        $this->assertStringNotContainsString('sab-price-history', (string) $encoded);
    }
}
