<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentSessionPrice extends Model
{
    protected $fillable = [
        'field_cluster_id',
        'field_type',
        'day_type',
        'session_type',
        'price_per_session',
    ];

    protected $casts = [
        'price_per_session' => 'decimal:2',
    ];

    // Relationships
    public function fieldCluster(): BelongsTo
    {
        return $this->belongsTo(FieldCluster::class);
    }
}