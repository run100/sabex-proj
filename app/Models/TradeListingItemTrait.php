<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeListingItemTrait extends Model
{
    public $timestamps = false;

    protected $table = 'seo_trade_listing_item_traits';

    protected $fillable = [
        'listing_item_id',
        'trait_name',
        'trait_name_snapshot',
        'value_multiplier_snapshot',
        'income_multiplier_snapshot',
        'sort_order',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'value_multiplier_snapshot' => 'float',
            'income_multiplier_snapshot' => 'float',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TradeListingItem::class, 'listing_item_id');
    }
}
