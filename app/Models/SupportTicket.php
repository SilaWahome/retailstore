<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject',
        'message',
        'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
