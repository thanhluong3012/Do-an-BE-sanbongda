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
        'tournament_rules_file',
        'format',
        'field_type',
        'max_teams',
        'min_teams',
        'registration_start',
        'registration_end',
        'start_date',
        'end_date',
        'status',
        'schedule_published',
        'field_cluster_id',
        'organizer_id',
        'payment_method',
        'payment_status',
        'payment_date',
        'payment_proof',
    ];

    protected $casts = [
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'schedule_published' => 'boolean',
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

    /**
     * Đếm số đội đã được approve
     */
    public function approvedTeamsCount(): int
    {
        return $this->registrations()->where('registration_status', 'approved')->count();
    }

    /**
     * Kiểm tra giải đã đủ đội chưa
     */
    public function isFull(): bool
    {
        return $this->approvedTeamsCount() >= $this->max_teams;
    }

    /**
     * Số chỗ còn trống
     */
    public function availableSlots(): int
    {
        $slots = $this->max_teams - $this->approvedTeamsCount();
        return max(0, $slots);
    }
}