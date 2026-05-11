<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'user_id',
        'tournament_id',
        'booking_type',
        'status',
        'field_id',
        'day_book',
        'start_time',
        'end_time',
        'total_price',
        'payment_status',
        'payment_method',
        'payment_proof',
        'payment_confirmed_at',
        'confirmed_by_user_id',
        'payment_note',
    ];

    protected $casts = [
        'day_book' => 'date',
        'booking_type' => 'string',
        'status' => 'string',
        'payment_status' => 'string',
        'payment_method' => 'string',
        'payment_confirmed_at' => 'datetime',
    ];

    protected $appends = ['payment_proof_url', 'booking_type_label'];

    /**
     * Accessor để lấy full URL của ảnh bill
     */
    public function getPaymentProofUrlAttribute()
    {
        if ($this->payment_proof) {
            return $this->payment_proof;
        }
        return null;
    }

    /**
     * Accessor để lấy label của booking_type
     */
    public function getBookingTypeLabelAttribute()
    {
        return match($this->booking_type) {
            'regular' => 'Đặt sân thường',
            'tournament' => 'Booking sân giải đấu',
            default => $this->booking_type,
        };
    }

    /**
     * Quan hệ với bảng users
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với bảng fields
     */
    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Quan hệ với bảng tournaments (nếu là booking giải đấu)
     */
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Quan hệ với user xác nhận thanh toán (owner/admin)
     */
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
