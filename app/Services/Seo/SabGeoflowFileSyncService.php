<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class SabGeoflowFileSyncService
{
    /**
     * @return list<array{source: string, destination: string, status: string}>
     */
    public function syncFiles(bool $dryRun = false): array
    {
        $report = [];
        foreach ($this->filePairs() as $source => $destination) {
            $report[] = $this->copyIfChanged($source, $destination, $dryRun);
        }

        return $report;
    }

    /**
     * @return array{ok: bool, output: string}
     */
    public function runExport(bool $dryRun = false): array
    {
        $script = (string) config('sab.geoflow_export_script');
        if ($script === '' || ! is_file($script)) {
            return [
                'ok' => false,
                'output' => 'GEOFlow export script not found: '.$script,
            ];
        }

        $command = [PHP_BINARY, $script];
        if ($dryRun) {
            $command[] = '--dry-run';
        }

        $process = new Process($command, dirname($script), [
            'SABEX_ROOT' => base_path(),
        ], null, 300);
        $process->run();

        return [
            'ok' => $process->isSuccessful(),
            'output' => trim($process->getOutput()."\n".$process->getErrorOutput()),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function filePairs(): array
    {
        $root = rtrim((string) config('sab.geoflow_root'), '/');
        $backend = $root.'/backend';

        return [
            $backend.'/resources/seo/sab/codes.json' => resource_path('seo/sab/codes.json'),
            $backend.'/resources/seo/sab/codes-i18n.json' => resource_path('seo/sab/codes-i18n.json'),
            $backend.'/resources/seo/sab/codes-i18n-east.json' => resource_path('seo/sab/codes-i18n-east.json'),
            $backend.'/resources/seo/sab/codes-i18n-west.json' => resource_path('seo/sab/codes-i18n-west.json'),
            $backend.'/storage/app/seo/sab-i18n.json' => storage_path('app/seo/sab-i18n.json'),
            $backend.'/storage/app/seo/sab-exist-count-gallery.json' => storage_path('app/seo/sab-exist-count-gallery.json'),
        ];
    }

    /**
     * @return array{source: string, destination: string, status: string}
     */
    private function copyIfChanged(string $source, string $destination, bool $dryRun = false): array
    {
        if (! is_file($source)) {
            return ['source' => $source, 'destination' => $destination, 'status' => 'missing_source'];
        }

        $sourceHash = hash_file('sha256', $source);
        $destinationHash = is_file($destination) ? hash_file('sha256', $destination) : '';
        if (hash_equals((string) $sourceHash, (string) $destinationHash)) {
            return ['source' => $source, 'destination' => $destination, 'status' => 'unchanged'];
        }

        if ($dryRun) {
            return ['source' => $source, 'destination' => $destination, 'status' => 'would_copy'];
        }

        File::ensureDirectoryExists(dirname($destination));
        File::copy($source, $destination);

        return ['source' => $source, 'destination' => $destination, 'status' => 'copied'];
    }
}
