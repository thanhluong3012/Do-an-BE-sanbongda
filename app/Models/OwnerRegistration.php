<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'image_code',
        'code_id',
        'business_name',
        'address',
        'status',
        'rejected_reason',
        'approved_by',
        'approved_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Quan hệ với admin đã duyệt
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
