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

    /**
     * Quan hệ với bảng books - Field có thể có nhiều đặt sân
     */
    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
