<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TradeReport extends Model
{
    public $timestamps = false;

    protected $table = 'seo_trade_reports';

    protected $fillable = [
        'public_id',
        'reporter_user_id',
        'listing_id',
        'reported_user_id',
        'reason',
        'description',
        'status',
        'resolution_note',
        'reviewed_by',
        'reviewed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $report): void {
            if (blank($report->public_id)) {
                $report->public_id = (string) Str::ulid();
            }
            if ($report->created_at === null) {
                $report->created_at = now();
            }
            if (blank($report->status)) {
                $report->status = 'open';
            }
        });
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'reporter_user_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(TradeListing::class, 'listing_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'reported_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
