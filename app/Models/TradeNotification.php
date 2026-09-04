<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeNotification extends Model
{
    public $timestamps = false;

    protected $table = 'seo_trade_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'listing_id',
        'join_request_id',
        'actor_user_id',
        'title',
        'message',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            if ($notification->created_at === null) {
                $notification->created_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'user_id');
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
