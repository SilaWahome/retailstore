<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrder extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'table_id',
        'server_id',
        'guest_count',
        'status',
        'subtotal_ugx',
        'tax_ugx',
        'total_amount_ugx',
        'paid_amount_ugx',
        'payment_method',
        'payment_status',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'subtotal_ugx' => 'decimal:2',
        'tax_ugx' => 'decimal:2',
        'total_amount_ugx' => 'decimal:2',
        'paid_amount_ugx' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function table()
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    public function server()
    {
        return $this->belongsTo(User::class, 'server_id');
    }

    public function items()
    {
        return $this->hasMany(RestaurantOrderItem::class, 'order_id');
    }

    public function tickets()
    {
        return $this->hasMany(KitchenTicket::class, 'order_id');
    }

    public function recalculateTotals(float $taxRate = 0.0): void
    {
        $subtotal = $this->items->sum(function ($item) {
            return ($item->unit_price + $item->modifiers_total) * $item->quantity;
        });

        $tax = round($subtotal * ($taxRate / 100), 2);
        $this->update([
            'subtotal_ugx' => $subtotal,
            'tax_ugx' => $tax,
            'total_amount_ugx' => $subtotal + $tax,
        ]);
    }
}

