<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'unit',
        'stock_qty',
        'low_stock_threshold',
        'cost_per_unit',
    ];

    protected $casts = [
        'stock_qty' => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
    ];
}

