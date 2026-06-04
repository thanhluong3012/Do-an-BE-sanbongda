<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPlayerPenalty extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'card_type',
    ];

    // Relationships
    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'player_id');
    }
}
