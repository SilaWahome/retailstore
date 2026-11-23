<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'description',
        'price_ugx',
        'station',
        'printer_ip',
        'color_tag',
        'is_available',
    ];

    protected $casts = [
        'price_ugx' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(MenuModifierGroup::class, 'menu_item_modifier_group', 'menu_item_id', 'modifier_group_id');
    }

    public function recipes()
    {
        return $this->hasMany(MenuRecipe::class, 'menu_item_id');
    }
}

