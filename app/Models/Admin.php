<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The guard this model authenticates against.
     */
    protected $guard = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'two_factor_secret',
        'two_factor_enabled_at',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    /**
     * The TOTP secret is encrypted with Laravel Crypt before storage and
     * decrypted on read — it never sits in the database as plaintext.
     */
    protected function twoFactorSecret(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function isSuperAdmin(): bool
    {
        return ($this->role ?? 'staff') === 'super_admin';
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled_at !== null
            && ! empty($this->getAttributes()['two_factor_secret']);
    }
}
