<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'country_code',
        'country_name',
        'pricing_region',
        'is_blocked',
        'google_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_blocked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Country is immutable for customers after signup (only an admin may change it).
     */
    public function isFromIndia(): bool
    {
        return $this->country_code === 'IN';
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Order::class);
    }

    /**
     * Rating eligibility, enforced server-side (RatingPolicy +
     * RatingController). A customer may rate a book only when they own it
     * through an order whose status is PAID — pending/failed/cancelled
     * orders and refunded orders (status becomes 'refunded') never count.
     */
    public function ownsBook(Book $book): bool
    {
        return OrderItem::query()
            ->where('book_id', $book->id)
            ->whereHas('order', fn ($q) => $q
                ->where('user_id', $this->id)
                ->where('status', Order::STATUS_PAID))
            ->exists();
    }
}
