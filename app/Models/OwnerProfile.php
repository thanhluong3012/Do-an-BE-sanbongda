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
        'bank_id',           // Mã ngân hàng (BIN Code)
        'account_no',        // Số tài khoản
        'account_name',      // Tên chủ tài khoản
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

    /**
     * Kiểm tra owner có thông tin bank chưa
     */
    public function hasBankInfo(): bool
    {
        return !empty($this->bank_id) &&
               !empty($this->account_no) &&
               !empty($this->account_name);
    }

    /**
     * Lấy thông tin bank dưới dạng array
     */
    public function getBankInfo(): ?array
    {
        if (!$this->hasBankInfo()) {
            return null;
        }

        return [
            'bank_id' => $this->bank_id,
            'account_no' => $this->account_no,
            'account_name' => $this->account_name,
        ];
    }
}
