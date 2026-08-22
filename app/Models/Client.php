<?php

namespace App\Models;

use App\Enums\ClientTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'phone',
        'email',
        'password',
        'status',
        'type',
        'balance',
        'address',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'type' => ClientTypeEnum::class,
        'balance' => 'double',
        'is_active' => 'boolean',
    ];

    public function details(): HasOne
    {
        return $this->hasOne(ClientDetail::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(ClientAddress::class, 'client_id');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'client_id');
    }

    public function visitorSessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class, 'client_id');
    }
}
