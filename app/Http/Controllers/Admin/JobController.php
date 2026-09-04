<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabGeoflowFileSyncService;
use App\Services\Seo\SabRotCalculatorSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    public function geoflow(Request $request, SabGeoflowFileSyncService $sync): JsonResponse
    {
        $dryRun = $request->boolean('dry_run');
        $export = $sync->runExport($dryRun);
        $files = $sync->syncFiles($dryRun);
        Log::info('admin.geoflow-sync', ['ok' => $export['ok'], 'dry_run' => $dryRun]);

        return response()->json([
            'ok' => $export['ok'],
            'output' => $export['output'],
            'files' => $files,
        ], $export['ok'] ? 200 : 422);
    }

    public function calculator(SabRotCalculatorSyncService $sync): JsonResponse
    {
        $result = $sync->refresh();
        Log::info('admin.calculator-refresh', $result);

        return response()->json(['ok' => true, 'result' => $result]);
    }
}
