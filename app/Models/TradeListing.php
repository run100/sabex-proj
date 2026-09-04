<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TradeListing extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_HIDDEN = 'hidden';

    protected $table = 'seo_trade_listings';

    protected $fillable = [
        'public_id',
        'owner_user_id',
        'counterparty_user_id',
        'status',
        'result_snapshot',
        'offering_value_snapshot',
        'looking_value_snapshot',
        'value_difference_snapshot',
        'difference_percent_snapshot',
        'note',
        'views_count',
        'accepted_at',
        'pending_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'offering_value_snapshot' => 'float',
            'looking_value_snapshot' => 'float',
            'value_difference_snapshot' => 'float',
            'difference_percent_snapshot' => 'float',
            'views_count' => 'integer',
            'accepted_at' => 'datetime',
            'pending_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $listing): void {
            if (blank($listing->public_id)) {
                $listing->public_id = (string) Str::ulid();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'owner_user_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(TradeUser::class, 'counterparty_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TradeListingItem::class, 'listing_id');
    }

    public function offeringItems(): HasMany
    {
        return $this->items()->where('side', TradeListingItem::SIDE_OFFERING)->orderBy('slot_no');
    }

    public function lookingItems(): HasMany
    {
        return $this->items()->where('side', TradeListingItem::SIDE_LOOKING)->orderBy('slot_no');
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(TradeJoinRequest::class, 'listing_id');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(TradeConfirmation::class, 'listing_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TradeEvent::class, 'listing_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isPendingLike(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PENDING_CONFIRMATION], true);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
