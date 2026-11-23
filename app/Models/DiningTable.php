<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DiningTable extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'area_id',
        'table_number',
        'seating_capacity',
        'shape',
        'has_floral',
        'pos_x',
        'pos_z',
        'width_m',
        'depth_m',
        'rotation_deg',
        'status',
        'current_order_id',
    ];

    protected $casts = [
        'seating_capacity' => 'integer',
        'has_floral' => 'boolean',
        'pos_x' => 'float',
        'pos_z' => 'float',
        'width_m' => 'float',
        'depth_m' => 'float',
        'rotation_deg' => 'integer',
    ];

    public function area()
    {
        return $this->belongsTo(DiningArea::class, 'area_id');
    }

    public function currentOrder()
    {
        return $this->belongsTo(RestaurantOrder::class, 'current_order_id');
    }
}
