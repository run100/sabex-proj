<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'domain',
        'base_url',
        'output_path',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    public function games(): HasMany
    {
        return $this->hasMany(SeoGame::class);
    }

    public function newsArticles(): HasMany
    {
        return $this->hasMany(SeoNewsArticle::class);
    }
}
