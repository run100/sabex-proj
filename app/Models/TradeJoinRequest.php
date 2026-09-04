<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TradeJoinRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_AUTO_REJECTED = 'auto_rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public $timestamps = false;

    protected $table = 'seo_trade_join_requests';

    protected $fillable = [
        'public_id',
        'listing_id',
        'requester_user_id',
        'owner_user_id',
        'status',
        'note',
        'created_at',
        'accepted_at',
        'rejected_at',
        'cancelled_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (blank($request->public_id)) {
                $request->public_id = (string) Str::ulid();
            }
            if ($request->created_at === null) {
                $request->created_at = now();
            }
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(TradeListing::class, 'listing_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'requester_user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'owner_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
