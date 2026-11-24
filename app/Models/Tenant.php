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
        'monthly_fee_ugx',
        'pending_bill_ugx',
        'tax_rate',
        'subscription_status',
        'subscription_package',
        'subscription_ends_at',
    ];

    protected $casts = [
        'monthly_fee_ugx' => 'decimal:2',
        'pending_bill_ugx' => 'decimal:2',
    ];

    public function effectiveMonthlyFeeUgx(): float
    {
        $customFee = (float) ($this->monthly_fee_ugx ?? 0);
        if ($customFee > 0) {
            return $customFee;
        }

        $planPrice = (float) ($this->plan?->price_ugx ?? 0);
        return $planPrice > 0 ? $planPrice : 20000.0;
    }

    public function pendingBillAmountUgx(): float
    {
        $explicitPending = (float) ($this->pending_bill_ugx ?? 0);
        if ($explicitPending > 0) {
            return $explicitPending;
        }

        $isExpiredOrSuspended = !in_array($this->subscription_status, ['active', 'trial'])
            || ($this->subscription_ends_at && \Carbon\Carbon::parse($this->subscription_ends_at)->isPast());

        $isDueSoon = $this->subscription_ends_at
            && \Carbon\Carbon::parse($this->subscription_ends_at)->lte(now()->addDays(5));

        if ($isExpiredOrSuspended || $isDueSoon) {
            return $this->effectiveMonthlyFeeUgx();
        }

        return 0.0;
    }

    public function isBillDueOrPromptable(): bool
    {
        return $this->pendingBillAmountUgx() > 0;
    }

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
