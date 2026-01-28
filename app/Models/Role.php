<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // Role constants for easy reference
    public const USER = 'user';
    public const ADMIN = 'admin';
    public const SUPERADMIN = 'superadmin';

    /**
     * Get users with this role
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
