<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IoTecTransaction extends Model
{
    protected $table = 'iotec_transactions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'sale_id',
        'type',
        'network',
        'phone_number',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'status',
        'external_id',
        'iotec_request_id',
        'iotec_transaction_id',
        'payer_or_payee_name',
        'notes',
        'error_message',
        'response_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'response_data' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
