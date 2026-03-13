<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'user_id',
        'tournament_id',
        'booking_type',
        'field_id',
        'day_book',
        'start_time',
        'end_time',
        'total_price',
    ];

    protected $casts = [
        'day_book' => 'date',
        'booking_type' => 'string',
    ];

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
}
