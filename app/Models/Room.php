<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'housekeeper_note',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'room_id');
    }

    public function activeBooking()
    {
        return $this->hasOne(Booking::class, 'room_id')
            ->where('status', 'checked_in')
            ->latestOfMany();
    }
}

