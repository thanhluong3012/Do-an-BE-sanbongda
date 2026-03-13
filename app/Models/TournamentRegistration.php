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
        'notes',
        'admin_notes',
        'rejected_reason',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'payment_date' => 'datetime',
    ];

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
