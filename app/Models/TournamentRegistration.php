<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistration extends Model
{
    protected $fillable = [
        'tournament_id',
        'team_id',
        'registration_date',
        'registration_status',
        'payment_status',
        'payment_method',
        'payment_date',
        'payment_proof',
        'payment_confirmed_at',
        'payment_note',
        'notes',
        'admin_notes',
        'rejected_reason',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'payment_date' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'payment_status' => 'string',
        'payment_method' => 'string',
    ];

    protected $appends = ['payment_proof_url'];

    /**
     * Accessor để lấy full URL của ảnh bill
     */
    public function getPaymentProofUrlAttribute()
    {
        if ($this->payment_proof) {
            return asset('storage/' . $this->payment_proof);
        }
        return null;
    }

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
