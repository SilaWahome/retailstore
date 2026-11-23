<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasTenant;

    /**
     * The measurement modes a product can use.
     *  - unit   : sold in whole pieces (default, existing behaviour)
     *  - weight : sold by mass (kg, g, lbs …)
     *  - volume : sold by volume (L, ml, oz …)
     *  - length : sold by length (m, cm, ft …)
     *  - pack   : sold in bundles/packs
     */
    const SOLD_BY_OPTIONS = ['unit', 'weight', 'volume', 'length', 'pack'];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'category',
        // Bulk / measurement fields
        'sold_by',      // unit | weight | volume | length | pack
        'base_unit',    // pcs | kg | g | L | ml | m | cm | pack …
        'sku',
        'image_path',
        'price',        // per base_unit (used when no portion is chosen)
        'cost',
        'quantity',     // stored in base units, decimal precision 3
        'low_stock_threshold',
        'is_active',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'cost'                => 'decimal:2',
        'quantity'            => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
        'is_active'           => 'boolean',
    ];

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Returns true when the product uses a non-unit measurement mode. */
    public function isBulk(): bool
    {
        return $this->sold_by !== 'unit';
    }

    /** Human-readable stock label e.g. "150.500 kg" */
    public function stockLabel(): string
    {
        return rtrim(rtrim(number_format((float)$this->quantity, 3, '.', ','), '0'), '.') . ' ' . $this->base_unit;
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** Preset portion sizes for bulk / liquid products. */
    public function portions()
    {
        return $this->hasMany(ProductPortion::class)->orderBy('sort_order')->orderBy('quantity');
    }
}
