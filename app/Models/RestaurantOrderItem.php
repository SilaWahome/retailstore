<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name',
        'unit_price',
        'modifiers_total',
        'quantity',
        'seat_number',
        'modifiers',
        'special_notes',
        'status',
        'is_paid',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'modifiers_total' => 'decimal:2',
        'modifiers' => 'array',
        'is_paid' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(RestaurantOrder::class, 'order_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }
}

