<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'code_id',
        'status',
        'password',
        'email_verified_at',
        'email_verification_token',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Quan hệ với bảng roles
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Quan hệ với bảng owner_profiles - User có thể có 1 owner profile
     */
    public function ownerProfile()
    {
        return $this->hasOne(OwnerProfile::class);
    }

    /**
     * Quan hệ với bảng staffs - User có thể là 1 staff
     */
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Quan hệ với bảng books - User có thể có nhiều đặt sân
     */
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    /**
     * Các đội mà user là captain (đội trưởng)
     */
    public function captainOfTeams()
    {
        return $this->hasMany(Team::class, 'captain_user_id');
    }

    /**
     * JWT: return the identifier stored in the token
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * JWT: return a key value array, containing any custom claims
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
