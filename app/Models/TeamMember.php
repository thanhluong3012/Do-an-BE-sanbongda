<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'team_id',
        'player_name',
        'code_id',
        'jersey_number',
        'position',
        'total_goals',
        'yellow_cards',
        'red_cards',
    ];

    protected $casts = [
        'total_goals' => 'integer',
        'yellow_cards' => 'integer',
        'red_cards' => 'integer',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
