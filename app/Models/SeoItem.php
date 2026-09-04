<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'seo_game_id',
        'slug',
        'name',
        'display_name',
        'rarity',
        'description',
        'summary',
        'is_publish_html',
        'is_listed',
        'is_tradable',
        'is_hot',
        'hot_rank',
        'total_exists',
        'rarest_mutation_name',
        'rarest_mutation_count',
        'rarest_trait_name',
        'rarest_trait_count',
        'avg_rebirth',
        'avg_coins_raw',
        'stats_updated_at',
        'image_url',
        'local_image_url',
        'source_page_url',
        'wiki_page_url',
        'wiki_page_title',
        'wiki_page_id',
        'wiki_page_status',
        'wiki_checked_at',
        'purchase_url',
        'rot_rocks_description_html',
        'rot_rocks_description_text',
        'rot_rocks_description_source_url',
        'rot_rocks_description_collected_at',
        'exist_estimate_low',
        'exist_estimate_high',
        'exist_estimate_reason',
        'exist_estimate_confidence',
        'exist_estimate_collected_at',
        'attributes_json',
        'sort_order',
        'supreme_value',
        'range_value',
        'stability',
        'origin',
        'rarity_number',
        'desc_json',
        'trend_label',
        'trend_judgment',
        'trend_direction',
        'trend_change_abs',
        'trend_change_pct',
        'trend_score',
    ];

    protected function casts(): array
    {
        return [
            'attributes_json' => 'array',
            'desc_json' => 'array',
            'is_publish_html' => 'boolean',
            'is_listed' => 'boolean',
            'is_tradable' => 'boolean',
            'is_hot' => 'boolean',
            'hot_rank' => 'integer',
            'total_exists' => 'integer',
            'rarest_mutation_count' => 'integer',
            'rarest_trait_count' => 'integer',
            'avg_rebirth' => 'decimal:2',
            'stats_updated_at' => 'datetime',
            'wiki_page_id' => 'integer',
            'wiki_checked_at' => 'datetime',
            'rot_rocks_description_collected_at' => 'datetime',
            'exist_estimate_low'          => 'integer',
            'exist_estimate_high'         => 'integer',
            'exist_estimate_collected_at' => 'datetime',
            'sort_order' => 'integer',
            'rarity_number' => 'integer',
            'trend_change_abs' => 'decimal:4',
            'trend_change_pct' => 'decimal:6',
            'trend_score' => 'decimal:6',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(SeoGame::class, 'seo_game_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(SeoItemVariant::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SeoItemTranslation::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(SeoItemAlias::class);
    }

    public function translation(string $locale): ?SeoItemTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }
}
