<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradeListingItem extends Model
{
    public const SIDE_OFFERING = 'offering';

    public const SIDE_LOOKING = 'looking_for';

    public $timestamps = false;

    protected $table = 'seo_trade_listing_items';

    protected $fillable = [
        'listing_id',
        'side',
        'slot_no',
        'seo_item_id',
        'slug_snapshot',
        'brainrot_name_snapshot',
        'image_url_snapshot',
        'seo_item_variant_id',
        'mutation_name_snapshot',
        'base_value_snapshot',
        'mutation_value_multiplier_snapshot',
        'trait_value_multiplier_snapshot',
        'final_value_snapshot',
        'base_income_snapshot',
        'final_income_snapshot',
        'demand_snapshot',
        'exist_count_snapshot',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'slot_no' => 'integer',
            'seo_item_id' => 'integer',
            'seo_item_variant_id' => 'integer',
            'base_value_snapshot' => 'float',
            'mutation_value_multiplier_snapshot' => 'float',
            'trait_value_multiplier_snapshot' => 'float',
            'final_value_snapshot' => 'float',
            'base_income_snapshot' => 'float',
            'final_income_snapshot' => 'float',
            'exist_count_snapshot' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(TradeListing::class, 'listing_id');
    }

    public function seoItem(): BelongsTo
    {
        return $this->belongsTo(SeoItem::class, 'seo_item_id');
    }

    public function traits(): HasMany
    {
        return $this->hasMany(TradeListingItemTrait::class, 'listing_item_id')->orderBy('sort_order');
    }
}
