<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class KitchenTicket extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'table_id',
        'server_id',
        'station',
        'status',
        'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(RestaurantOrder::class, 'order_id');
    }

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
        return $this->hasMany(KitchenTicketItem::class, 'ticket_id');
    }
}

