<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenTicketItem extends Model
{
    protected $fillable = [
        'ticket_id',
        'order_item_id',
        'menu_item_id',
        'item_name',
        'quantity',
        'modifiers_summary',
        'special_notes',
    ];

    public function ticket()
    {
        return $this->belongsTo(KitchenTicket::class, 'ticket_id');
    }
}

