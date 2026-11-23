<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPortion extends Model
{
    protected $fillable = [
        'product_id',
        'label',
        'quantity',
        'price',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price'    => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
