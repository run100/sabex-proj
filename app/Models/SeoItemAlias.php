<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoItemAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_item_id',
        'seo_value_source_id',
        'alias_name',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SeoItem::class, 'seo_item_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SeoValueSource::class, 'seo_value_source_id');
    }
}
