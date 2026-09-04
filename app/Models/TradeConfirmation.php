<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeConfirmation extends Model
{
    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    protected $table = 'seo_trade_confirmations';

    protected $fillable = [
        'listing_id',
        'user_id',
        'confirmation',
        'note',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(TradeListing::class, 'listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'user_id');
    }
}
