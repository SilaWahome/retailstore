<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'symbol',
        'exchange_rate',
        'is_base',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'exchange_rate' => 'decimal:4',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Resolve the active display currency for the given (or authenticated) tenant,
     * honoring the session switcher first, then the tenant's base currency.
     */
    public static function activeForTenant(?int $tenantId = null): self
    {
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;

        if (!$tenantId) {
            return new self(['code' => 'USD', 'symbol' => '$', 'exchange_rate' => 1, 'is_base' => true]);
        }

        $activeCurrencyId = session('active_currency_id');

        if ($activeCurrencyId) {
            $active = self::where('tenant_id', $tenantId)->where('id', $activeCurrencyId)->first();
            if ($active) {
                return $active;
            }
        }

        return self::where('tenant_id', $tenantId)->where('is_base', true)->first()
            ?: self::where('tenant_id', $tenantId)->first()
            ?: new self(['code' => 'USD', 'symbol' => '$', 'exchange_rate' => 1, 'is_base' => true]);
    }
}
