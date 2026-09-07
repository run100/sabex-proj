<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class AccessLogController extends Controller
{
    public function index(): JsonResponse
    {
        if (! Schema::hasTable('seo_access_logs')) {
            return response()->json(['logs' => []]);
        }

        $logs = AccessLog::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'logs' => $logs->map(fn (AccessLog $log): array => [
                'id' => $log->id,
                'actor_type' => $log->actor_type,
                'actor_id' => $log->actor_id,
                'action' => $log->action,
                'ip' => $log->ip,
                'user_agent' => $log->user_agent,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'created_at' => $log->created_at,
            ])->all(),
        ]);
    }
}
