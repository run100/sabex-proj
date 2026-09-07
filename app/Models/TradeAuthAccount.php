<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeAuthAccount extends Model
{
    public const PROVIDER_EMAIL = 'email';

    public const PROVIDER_ROBLOX = 'roblox';

    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_LOCAL = 'local';

    protected $table = 'seo_trade_auth_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_uid',
        'provider_username',
        'avatar_url',
        'bound_at',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'bound_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'user_id');
    }
}
