<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeUserBlock extends Model
{
    public $timestamps = false;

    protected $table = 'seo_trade_user_blocks';

    protected $fillable = [
        'user_id',
        'blocked_user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $block): void {
            if ($block->created_at === null) {
                $block->created_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'user_id');
    }

    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'blocked_user_id');
    }
}
