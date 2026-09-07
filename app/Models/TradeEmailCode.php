<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeEmailCode extends Model
{
    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_BIND = 'bind';

    public const UPDATED_AT = null;

    protected $table = 'seo_trade_email_codes';

    protected $fillable = [
        'email',
        'code',
        'purpose',
        'attempts',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
