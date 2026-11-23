<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',      // decimal: stored in base units
        'unit_label',    // e.g. kg, ml, pcs
        'portion_label', // e.g. '1/2 kg', '250 ml' (null for free-type)
        'price',
        'total',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'total'    => 'decimal:2',
        'quantity' => 'decimal:3',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
