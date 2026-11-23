<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'business_name',
        'logo',
        'phone',
        'location',
        'business_type',
        'current_plan_id',
        'tax_rate',
        'subscription_status',
        'subscription_package',
        'subscription_ends_at',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'current_plan_id');
    }

    public function canAccess(string $featureKey): bool
    {
        if (!$this->current_plan_id) {
            return false;
        }

        return $this->plan()
            ->whereHas('features', fn ($q) => $q->where('feature_key', $featureKey))
            ->exists();
    }

    public function isRestaurantOrHotel(): bool
    {
        return in_array($this->business_type, ['restaurant', 'hotel']);
    }

    public function isHotel(): bool
    {
        return $this->business_type === 'hotel';
    }

    public function roomLimit(): ?int
    {
        return $this->plan?->room_limit;
    }

    public function featureKeys(): array
    {
        if (!$this->plan) {
            return [];
        }

        return $this->plan->features()->pluck('feature_key')->all();
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function currencies()
    {
        return $this->hasMany(Currency::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }
}
