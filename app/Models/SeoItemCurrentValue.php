<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoItemCurrentValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_item_variant_id',
        'seo_value_source_id',
        'collected_at',
        'changed_at',
        'source_updated_at',
        'exist_count_raw',
        'exist_count_normalized',
        'value_raw',
        'value_normalized',
        'currency',
        'demand',
        'confidence',
        'source_payload_hash',
        'last_observation_id',
    ];

    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'changed_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'exist_count_normalized' => 'integer',
            'value_normalized' => 'decimal:4',
            'confidence' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(SeoItemVariant::class, 'seo_item_variant_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SeoValueSource::class, 'seo_value_source_id');
    }

    public function lastObservation(): BelongsTo
    {
        return $this->belongsTo(SeoItemObservation::class, 'last_observation_id');
    }
}
