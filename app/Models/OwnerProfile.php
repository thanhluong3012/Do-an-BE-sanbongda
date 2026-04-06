<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'address',
        'image_code',
        'code_id',
        'qr_code_image',
    ];

    /**
     * Quan hệ với bảng users - Mỗi profile thuộc về 1 user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với bảng staffs - Một chủ sân có nhiều nhân viên
     */
    public function staffs()
    {
        return $this->hasMany(Staff::class, 'owner_id');
    }

    /**
     * Quan hệ với bảng field_clusters - Một chủ sân có nhiều cụm sân
     */
    public function fieldClusters()
    {
        return $this->hasMany(FieldCluster::class, 'owner_id');
    }
}
