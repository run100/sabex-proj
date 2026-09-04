<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_site_id',
        'slug',
        'name',
        'source_url',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SeoSite::class, 'seo_site_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SeoItem::class);
    }
}
