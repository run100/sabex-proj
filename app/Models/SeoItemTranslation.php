<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoItemTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_item_id',
        'locale',
        'name',
        'description',
        'seo_title',
        'seo_description',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SeoItem::class, 'seo_item_id');
    }
}
