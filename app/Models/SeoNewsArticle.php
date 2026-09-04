<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoNewsArticle extends Model
{
    use HasFactory;

    public const TYPE_STATIC_PAGE = 100;

    public const TYPE_NEWS = 200;

    protected $fillable = [
        'seo_site_id',
        'type',
        'slug',
        'locale',
        'title',
        'excerpt',
        'cover_image_url',
        'body_html',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'sort_order',
        'is_system_log',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
            'is_system_log' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SeoSite::class, 'seo_site_id');
    }
}
