<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'po_number',
        'supplier_name',
        'status', // pending, received, cancelled
        'total_cost',
        'amount_paid',
        'payment_status',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
