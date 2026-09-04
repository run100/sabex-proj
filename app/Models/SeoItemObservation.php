<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoItemObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_item_variant_id',
        'seo_value_source_id',
        'seo_sync_run_id',
        'observed_at',
        'source_updated_at',
        'exist_count_raw',
        'exist_count_normalized',
        'value_raw',
        'value_normalized',
        'currency',
        'demand',
        'confidence',
        'source_payload_hash',
        'source_payload_json',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'exist_count_normalized' => 'integer',
            'value_normalized' => 'decimal:4',
            'confidence' => 'integer',
            'source_payload_json' => 'array',
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

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(SeoSyncRun::class, 'seo_sync_run_id');
    }
}
