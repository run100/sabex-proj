<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoValueSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'url',
        'parser_type',
        'priority',
        'is_primary_source',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'is_primary_source' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function observations(): HasMany
    {
        return $this->hasMany(SeoItemObservation::class);
    }

    public function currentValues(): HasMany
    {
        return $this->hasMany(SeoItemCurrentValue::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(SeoItemAlias::class);
    }
}
