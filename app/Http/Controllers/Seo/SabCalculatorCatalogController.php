<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabCalculatorCatalogService;
use Symfony\Component\HttpFoundation\Response;

class SabCalculatorCatalogController extends Controller
{
    public function manifest(): Response
    {
        return $this->fileResponse(SabCalculatorCatalogService::manifestPath(), 60, false);
    }

    public function bootstrap(string $version): Response
    {
        return $this->fileResponse(
            SabCalculatorCatalogService::releaseBootstrapPath($version),
            31536000,
            true,
        );
    }

    public function valueList(string $version): Response
    {
        return $this->fileResponse(
            SabCalculatorCatalogService::releasePath($version).'/value-list.json',
            31536000,
            true,
        );
    }

    public function chunk(string $version, string $chunk): Response
    {
        abort_unless(preg_match('/^[0-9]{2}$/', $chunk) === 1, 404);

        return $this->fileResponse(
            SabCalculatorCatalogService::releaseChunkPath($version, $chunk),
            31536000,
            true,
        );
    }

    private function fileResponse(string $path, int $maxAge, bool $immutable): Response
    {
        abort_unless(is_file($path) && is_readable($path), 404);

        $cacheControl = 'public, max-age='.$maxAge;
        if ($immutable) {
            $cacheControl .= ', immutable';
        }

        return response()->file($path, [
            'Cache-Control' => $cacheControl,
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
