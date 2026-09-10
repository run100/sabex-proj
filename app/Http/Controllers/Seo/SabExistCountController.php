<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabExistCountCatalogService;
use Symfony\Component\HttpFoundation\Response;

class SabExistCountController extends Controller
{
    public function data(): Response
    {
        $path = SabExistCountCatalogService::path();
        abort_unless(is_file($path) && is_readable($path), 404);

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
