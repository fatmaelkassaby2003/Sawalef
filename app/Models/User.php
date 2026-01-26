<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, JWTSubject
{
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        'nickname',
        'age',
        'gender',
        'country_ar',
        'country_en',
        'profile_image',
        'last_otp',
        'last_otp_expires_at',
        'is_admin',
        'wallet_balance',
        'gems',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'last_otp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'wallet_balance' => 'decimal:2',
        'gems' => 'integer',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function hobbies()
    {
        return $this->belongsToMany(Hobby::class, 'user_hobbies', 'user_id', 'hobby_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function packagePurchases()
    {
        return $this->hasMany(PackagePurchase::class);
    }

    public function siteNotifications()
    {
        return $this->hasMany(SiteNotification::class);
    }

    public function friendRequestsSent()
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function friendRequestsReceived()
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }

    /**
     * Check if the user is friends with another user.
     */
    public function isFriendWith($userId)
    {
        return FriendRequest::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('sender_id', $this->id)
                      ->where('receiver_id', $userId);
                })->orWhere(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                      ->where('receiver_id', $this->id);
                });
            })->exists();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
