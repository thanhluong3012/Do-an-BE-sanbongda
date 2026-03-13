<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'description',
        'format',
        'max_teams',
        'min_teams',
        'registration_start',
        'registration_end',
        'start_date',
        'end_date',
        'status',
        'field_cluster_id',
        'organizer_id',
    ];

    protected $casts = [
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function fieldCluster(): BelongsTo
    {
        return $this->belongsTo(FieldCluster::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TournamentSchedule::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(TournamentRound::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    /**
     * Booking các sân cụ thể cho giải đấu
     */
    public function fieldBookings(): HasMany
    {
        return $this->hasMany(Book::class, 'tournament_id');
    }
}
