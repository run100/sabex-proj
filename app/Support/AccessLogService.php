<?php

namespace App\Support;

use App\Models\AccessLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AccessLogService
{
    public static function ip(?Request $request = null): ?string
    {
        $ip = ($request ?? request())?->ip();

        return $ip ? mb_substr($ip, 0, 45) : null;
    }

    public static function userAgent(?Request $request = null): ?string
    {
        $agent = ($request ?? request())?->userAgent();

        return $agent ? mb_substr($agent, 0, 500) : null;
    }

    /**
     * @param  array<string, string|null>  $columns
     * @return array<string, string>
     */
    public static function attrs(string $table, array $columns): array
    {
        $out = [];
        foreach ($columns as $column => $ip) {
            if ($ip !== null && $ip !== '' && Schema::hasColumn($table, $column)) {
                $out[$column] = $ip;
            }
        }

        return $out;
    }

    public static function assign(Model $model, string $column, ?string $ip): void
    {
        if ($ip === null || $ip === '' || ! Schema::hasColumn($model->getTable(), $column)) {
            return;
        }
        $model->{$column} = $ip;
    }

    public static function write(
        string $actorType,
        ?int $actorId,
        string $action,
        ?string $ip = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?Request $request = null,
    ): void {
        if (! Schema::hasTable('seo_access_logs')) {
            return;
        }

        AccessLog::query()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'ip' => $ip ?? self::ip($request),
            'user_agent' => self::userAgent($request),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
        ]);
    }
}
