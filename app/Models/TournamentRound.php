<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentRound extends Model
{
    protected $fillable = [
        'tournament_id',
        'round_name',
        'round_type',
        'round_order',
        'start_date',
        'end_date',
        'is_knockout',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_knockout' => 'boolean',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'tournament_round_id');
    }
}
