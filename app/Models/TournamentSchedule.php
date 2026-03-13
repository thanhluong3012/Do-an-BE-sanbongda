<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentSchedule extends Model
{
    protected $fillable = [
        'tournament_id',
        'match_date',
        'session_type',
        'day_type',
        'number_of_fields',
        'price_per_field',
        'session_cost',
        'notes',
    ];

    protected $casts = [
        'match_date' => 'date',
        'price_per_field' => 'decimal:2',
        'session_cost' => 'decimal:2',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
