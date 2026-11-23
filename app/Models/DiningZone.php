<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DiningZone extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'area_id',
        'name',
        'zone_type',
        'pos_x',
        'pos_z',
        'width_m',
        'depth_m',
        'color',
    ];

    protected $casts = [
        'pos_x' => 'float',
        'pos_z' => 'float',
        'width_m' => 'float',
        'depth_m' => 'float',
    ];

    public function area()
    {
        return $this->belongsTo(DiningArea::class, 'area_id');
    }
}

