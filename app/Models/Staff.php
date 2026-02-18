<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staffs';

    protected $fillable = [
        'user_id',
        'owner_id',
        'code_id',
        'avatar',
    ];

    /**
     * Quan hệ với bảng users - Mỗi staff thuộc về 1 user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với bảng owner_profiles - Mỗi staff thuộc về 1 chủ sân
     */
    public function owner()
    {
        return $this->belongsTo(OwnerProfile::class, 'owner_id');
    }
}
