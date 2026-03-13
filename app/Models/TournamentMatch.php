<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'tournament_round_id',
        'tournament_group_id',
        'home_team_id',
        'away_team_id',
        'home_from_match_id',
        'away_from_match_id',
        'home_from_position',
        'away_from_position',
        'field_id',
        'match_date',
        'match_time',
        'match_number',
        'home_score',
        'away_score',
        'home_penalty_score',
        'away_penalty_score',
        'winner_team_id',
        'status',
        'referee_name',
        'notes',
    ];

    protected $casts = [
        'match_date' => 'date',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(TournamentRound::class, 'tournament_round_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'tournament_group_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function homeFromMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'home_from_match_id');
    }

    public function awayFromMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'away_from_match_id');
    }
}
