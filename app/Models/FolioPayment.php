<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioPayment extends Model
{
    protected $fillable = [
        'folio_id',
        'amount_ugx',
        'payment_method',
        'reference',
        'received_by',
    ];

    protected $casts = [
        'amount_ugx' => 'decimal:2',
    ];

    public function folio()
    {
        return $this->belongsTo(GuestFolio::class, 'folio_id');
    }
}

