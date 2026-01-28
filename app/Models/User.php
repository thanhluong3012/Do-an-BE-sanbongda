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
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Quan hệ với bảng roles
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
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
