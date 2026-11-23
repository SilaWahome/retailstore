<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class MenuModifierGroup extends Model
{
    use HasTenant;

    protected $fillable = ['tenant_id', 'name', 'min_selections', 'max_selections'];

    public function modifiers()
    {
        return $this->hasMany(MenuModifier::class, 'group_id');
    }
}

