<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class StationPrinter extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'station',
        'printer_name',
        'printer_ip',
        'printer_port',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'printer_port' => 'integer',
    ];
}

