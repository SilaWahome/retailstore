<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class GuestFolio extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'total_charges_ugx',
        'total_paid_ugx',
        'is_settled',
    ];

    protected $casts = [
        'total_charges_ugx' => 'decimal:2',
        'total_paid_ugx' => 'decimal:2',
        'is_settled' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function charges()
    {
        return $this->hasMany(FolioCharge::class, 'folio_id')->latest();
    }

    public function payments()
    {
        return $this->hasMany(FolioPayment::class, 'folio_id')->latest();
    }

    public function balance(): float
    {
        return max(0, (float) $this->total_charges_ugx - (float) $this->total_paid_ugx);
    }
}

