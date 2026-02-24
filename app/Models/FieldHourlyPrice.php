<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldHourlyPrice extends Model
{
    protected $fillable = [
        'field_cluster_id',
        'day_type',
        'field_type',
        'price_hour',
    ];

    public function fieldCluster()
    {
        return $this->belongsTo(FieldCluster::class, 'field_cluster_id');
    }
}
