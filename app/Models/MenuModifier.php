<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class MenuModifier extends Model
{
    use HasTenant;

    protected $fillable = ['tenant_id', 'group_id', 'name', 'extra_price'];

    protected $casts = [
        'extra_price' => 'decimal:2',
    ];

    public function group()
    {
        return $this->belongsTo(MenuModifierGroup::class, 'group_id');
    }
}

