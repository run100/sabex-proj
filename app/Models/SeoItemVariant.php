<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoItemVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_item_id',
        'variant_key',
        'variant_name',
        'variant_type',
        'mutation',
        'mutation_name',
        'trait',
        'trait_name',
        'exist_percentage',
        'multiplier',
        'sort_order',
        'attributes_json',
    ];

    protected function casts(): array
    {
        return [
            'attributes_json' => 'array',
            'exist_percentage' => 'decimal:4',
            'multiplier' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SeoItem::class, 'seo_item_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(SeoItemObservation::class);
    }

    public function currentValues(): HasMany
    {
        return $this->hasMany(SeoItemCurrentValue::class);
    }
}
