<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'business_type',
        'price_ugx',
        'billing_interval',
        'room_limit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price_ugx' => 'decimal:2',
        'room_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function features()
    {
        return $this->hasMany(PlanFeature::class, 'plan_id');
    }

    public function hasFeature(string $featureKey): bool
    {
        return $this->features()->where('feature_key', $featureKey)->exists();
    }
}

