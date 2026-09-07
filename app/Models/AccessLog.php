<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    public $timestamps = false;

    protected $table = 'seo_access_logs';

    protected $fillable = [
        'actor_type',
        'actor_id',
        'action',
        'ip',
        'user_agent',
        'subject_type',
        'subject_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'actor_id' => 'integer',
            'subject_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            if ($log->created_at === null) {
                $log->created_at = now();
            }
        });
    }
}
