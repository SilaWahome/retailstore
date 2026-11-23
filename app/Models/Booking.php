<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'guest_name',
        'guest_phone',
        'guest_id_number',
        'check_in_date',
        'check_out_date',
        'nightly_rate_ugx',
        'status',
        'notes',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'nightly_rate_ugx' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function folio()
    {
        return $this->hasOne(GuestFolio::class, 'booking_id');
    }
}

