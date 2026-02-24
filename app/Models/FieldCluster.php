<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldCluster extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'phone',
        'description',
        'image',
        'open_time',
        'close_time',
    ];

    public function owner()
    {
        return $this->belongsTo(OwnerProfile::class, 'owner_id');
    }

    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    public function hourlyPrices()
    {
        return $this->hasMany(FieldHourlyPrice::class, 'field_cluster_id');
    }
}
