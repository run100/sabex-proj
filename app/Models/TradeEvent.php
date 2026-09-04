<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeEvent extends Model
{
    public $timestamps = false;

    protected $table = 'seo_trade_events';

    protected $fillable = [
        'listing_id',
        'actor_user_id',
        'event_type',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if ($event->created_at === null) {
                $event->created_at = now();
            }
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(TradeListing::class, 'listing_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'actor_user_id');
    }
}
