<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class TradeUser extends Authenticatable
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_BANNED = 'banned';

    public const STATUS_DELETED = 'deleted';

    protected $table = 'seo_trade_users';

    protected $fillable = [
        'public_id',
        'roblox_sub',
        'username',
        'display_name',
        'avatar_url',
        'profile_url',
        'account_status',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->public_id)) {
                $user->public_id = (string) Str::ulid();
            }
        });
    }

    public function isActive(): bool
    {
        return $this->account_status === self::STATUS_ACTIVE;
    }

    public function listings(): HasMany
    {
        return $this->hasMany(TradeListing::class, 'owner_user_id');
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(TradeJoinRequest::class, 'requester_user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(TradeNotification::class, 'user_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(TradeUserBlock::class, 'user_id');
    }
}
