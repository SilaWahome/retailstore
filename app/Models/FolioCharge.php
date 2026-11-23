<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioCharge extends Model
{
    protected $fillable = [
        'folio_id',
        'source',
        'reference_id',
        'description',
        'amount_ugx',
        'created_by',
    ];

    protected $casts = [
        'amount_ugx' => 'decimal:2',
    ];

    public function folio()
    {
        return $this->belongsTo(GuestFolio::class, 'folio_id');
    }
}

