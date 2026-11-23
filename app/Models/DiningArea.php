<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DiningArea extends Model
{
    use HasTenant;

    protected $fillable = ['tenant_id', 'name', 'width_m', 'length_m', 'sort_order'];

    protected $casts = [
        'width_m' => 'float',
        'length_m' => 'float',
    ];

    public function tables()
    {
        return $this->hasMany(DiningTable::class, 'area_id')->orderBy('table_number');
    }

    public function zones()
    {
        return $this->hasMany(DiningZone::class, 'area_id')->orderBy('name');
    }
}
