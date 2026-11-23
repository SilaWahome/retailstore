<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'base_price_ugx',
        'capacity',
        'description',
    ];

    protected $casts = [
        'base_price_ugx' => 'decimal:2',
        'capacity' => 'integer',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'room_type_id');
    }
}

