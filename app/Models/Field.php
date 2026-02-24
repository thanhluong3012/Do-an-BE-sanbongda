<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $fillable = [
        'field_cluster_id',
        'field_type',
        'status',
    ];

    public function fieldCluster()
    {
        return $this->belongsTo(FieldCluster::class, 'field_cluster_id');
    }
}
