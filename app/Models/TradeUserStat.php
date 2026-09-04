<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeUserStat extends Model
{
    public $timestamps = false;

    protected $table = 'seo_trade_user_stats';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'trades_posted',
        'trades_joined',
        'trades_accepted',
        'trades_completed',
        'trades_failed',
        'trades_disputed',
        'completion_rate',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'completion_rate' => 'float',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'user_id');
    }
}
