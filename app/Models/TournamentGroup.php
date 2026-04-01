<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentGroup extends Model
{
    protected $fillable = [
        'tournament_id',
        'group_name',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(TournamentGroupStanding::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'tournament_group_id');
    }
}
