<?php

namespace App\Models;

use App\Support\TradeSchema;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class TradeUser extends Authenticatable
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_BANNED = 'banned';

    public const STATUS_DELETED = 'deleted';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_UNLISTED = 'unlisted';

    public const VISIBILITY_PRIVATE = 'private';

    public const MODERATION_CLEAR = 'clear';

    public const MODERATION_REVIEW = 'review';

    public const MODERATION_RESTRICTED = 'restricted';

    protected $table = 'seo_trade_users';

    protected $fillable = [
        'public_id',
        'profile_id',
        'roblox_sub',
        'roblox_user_id',
        'username',
        'display_name',
        'avatar_url',
        'profile_url',
        'email',
        'email_verified_at',
        'account_status',
        'profile_visibility',
        'moderation_status',
        'profile_index_eligible',
        'deleted_at',
        'posting_approved',
        'posting_approved_at',
        'password',
        'registered_ip',
        'last_login_at',
        'last_login_ip',
    ];

    protected $attributes = [
        'posting_approved' => true,
        'profile_visibility' => self::VISIBILITY_PUBLIC,
        'moderation_status' => self::MODERATION_CLEAR,
        'profile_index_eligible' => false,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'posting_approved' => 'boolean',
            'posting_approved_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'profile_index_eligible' => 'boolean',
            'deleted_at' => 'datetime',
            'roblox_user_id' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->public_id)) {
                $user->public_id = (string) Str::ulid();
            }
            if (blank($user->profile_id)) {
                $user->profile_id = (string) Str::ulid();
            }
            if (blank($user->profile_visibility)) {
                $user->profile_visibility = self::VISIBILITY_PUBLIC;
            }
            if (blank($user->moderation_status)) {
                $user->moderation_status = self::MODERATION_CLEAR;
            }
        });
    }

    public static function findPublic(string $key): ?self
    {
        return static::query()->where('profile_id', $key)->first();
    }

    public function isActive(): bool
    {
        return $this->account_status === self::STATUS_ACTIVE;
    }

    public function canPost(): bool
    {
        return $this->isActive() && $this->posting_approved;
    }

    public function approvePosting(): void
    {
        if ($this->posting_approved && $this->posting_approved_at !== null) {
            return;
        }

        $this->posting_approved = true;
        $this->posting_approved_at = $this->posting_approved_at ?? now();
    }

    public function profilePath(): string
    {
        return '/profile/'.$this->profile_id;
    }

    public function robloxProfileUrl(): ?string
    {
        if (blank($this->roblox_sub) || str_starts_with((string) $this->roblox_sub, 'local:')) {
            return filled($this->profile_url) ? (string) $this->profile_url : null;
        }

        return $this->profile_url ?: 'https://www.roblox.com/users/'.$this->roblox_sub.'/profile';
    }

    /**
     * @return list<string>
     */
    public function connectedProviders(): array
    {
        $providers = [];
        if (TradeSchema::identitiesReady()) {
            $providers = $this->identities()->pluck('provider')->all();
        }
        if (filled($this->email) && ! in_array(TradeAuthAccount::PROVIDER_EMAIL, $providers, true)) {
            $providers[] = TradeAuthAccount::PROVIDER_EMAIL;
        }
        if (filled($this->roblox_sub) && str_starts_with((string) $this->roblox_sub, 'local:')) {
            if (! in_array(TradeAuthAccount::PROVIDER_LOCAL, $providers, true)) {
                $providers[] = TradeAuthAccount::PROVIDER_LOCAL;
            }
        } elseif (filled($this->roblox_sub) && ! in_array(TradeAuthAccount::PROVIDER_ROBLOX, $providers, true)) {
            $providers[] = TradeAuthAccount::PROVIDER_ROBLOX;
        }

        return array_values($providers);
    }

    public function hasProvider(string $provider): bool
    {
        return in_array($provider, $this->connectedProviders(), true);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(TradeAuthAccount::class, 'user_id');
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
