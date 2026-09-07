<?php

namespace App\Models;

use Database\Factories\SeoUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'last_login_at', 'last_login_ip'])]
#[Hidden(['password', 'remember_token'])]
class SeoUser extends Authenticatable
{
    /** @use HasFactory<SeoUserFactory> */
    use HasFactory;

    protected $table = 'seo_users';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'super_admin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }
}
