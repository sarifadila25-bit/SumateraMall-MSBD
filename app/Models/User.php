<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class);
    }

    public function chatLogs(): HasMany
    {
        return $this->hasMany(ChatLog::class);
    }

    public function userVouchers(): HasMany
{
    return $this->hasMany(UserVoucher::class);
}

    public function reservations(): HasMany
    {
    return $this->hasMany(Reservation::class);
    }

    public function createdVouchers(): HasMany
    {      
    return $this->hasMany(Voucher::class, 'created_by');
    }

    public function isAdmin(): bool
    {
    return $this->role === 'admin';
    }   
}
