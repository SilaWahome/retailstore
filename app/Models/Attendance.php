<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'date',
        'check_in',
        'check_out',
        'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
