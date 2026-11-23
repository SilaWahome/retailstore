<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SAFE FOR LIVE DATABASES:
     *  - Every column addition is guarded with Schema::hasColumn()
     *  - INT -> DECIMAL type changes preserve all existing integer data
     *  - product_portions table is created only when absent
     *  - Back-fills existing rows with sensible defaults
     *
     * If you prefer phpMyAdmin, use database/safe_migrate_bulk_goods.sql
     * which does the same thing via raw SQL with INFORMATION_SCHEMA guards.
     */
    public function up(): void
    {
        // ── 1. products: add sold_by ─────────────────────────────────────
        if (!Schema::hasColumn('products', 'sold_by')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('sold_by', 20)->default('unit')->after('category');
            });
        }

        // ── 2. products: add base_unit ───────────────────────────────────
        if (!Schema::hasColumn('products', 'base_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('base_unit', 20)->default('pcs')->after('sold_by');
            });
        }

        // ── 3-5. MySQL-only INT -> DECIMAL(15,3) column conversions ─────
        if (DB::getDriverName() === 'mysql') {
            $qType = DB::selectOne("
                SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'products'
                  AND COLUMN_NAME  = 'quantity'
            ");
            if ($qType && $qType->DATA_TYPE === 'int') {
                DB::statement('ALTER TABLE `products` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000');
            }

            $lstType = DB::selectOne("
                SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'products'
                  AND COLUMN_NAME  = 'low_stock_threshold'
            ");
            if ($lstType && $lstType->DATA_TYPE === 'int') {
                DB::statement('ALTER TABLE `products` MODIFY COLUMN `low_stock_threshold` DECIMAL(15,3) NOT NULL DEFAULT 5.000');
            }

            $siType = DB::selectOne("
                SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'sale_items'
                  AND COLUMN_NAME  = 'quantity'
            ");
            if ($siType && $siType->DATA_TYPE === 'int') {
                DB::statement('ALTER TABLE `sale_items` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000');
            }
        }

        // ── 6. sale_items: add unit_label ────────────────────────────────
        if (!Schema::hasColumn('sale_items', 'unit_label')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->string('unit_label', 20)->default('pcs')->after('quantity');
            });
        }

        // ── 7. sale_items: add portion_label ─────────────────────────────
        if (!Schema::hasColumn('sale_items', 'portion_label')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->string('portion_label', 50)->nullable()->after('unit_label');
            });
        }

        // ── 8-10. MySQL-only INT -> DECIMAL(15,3) column conversions ────
        if (DB::getDriverName() === 'mysql') {
            $ilType = DB::selectOne("
                SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'inventory_logs'
                  AND COLUMN_NAME  = 'change_amount'
            ");
            if ($ilType && $ilType->DATA_TYPE === 'int') {
                DB::statement('ALTER TABLE `inventory_logs` MODIFY COLUMN `change_amount` DECIMAL(15,3) NOT NULL DEFAULT 0.000');
            }

            $poiType = DB::selectOne("
                SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'purchase_order_items'
                  AND COLUMN_NAME  = 'quantity'
            ");
            if ($poiType && $poiType->DATA_TYPE === 'int') {
                DB::statement('ALTER TABLE `purchase_order_items` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000');
            }

            $qiType = DB::selectOne("
                SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'quotation_items'
                  AND COLUMN_NAME  = 'quantity'
            ");
            if ($qiType && $qiType->DATA_TYPE === 'int') {
                DB::statement('ALTER TABLE `quotation_items` MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000');
            }
        }

        // ── 11. Create product_portions table ────────────────────────────
        if (!Schema::hasTable('product_portions')) {
            Schema::create('product_portions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                // Label shown to cashier e.g. "1/2 kg", "250 ml", "1 Litre"
                $table->string('label', 50)->comment('e.g. 1/2 kg, 250 ml, 1 Litre');
                // Quantity in base units to deduct from stock when this portion is sold
                $table->decimal('quantity', 15, 3)->comment('Stock deducted in base units');
                // Fixed price for this portion
                $table->decimal('price', 15, 2)->comment('Fixed selling price for this portion');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // ── 12. Back-fill defaults for existing rows ─────────────────────
        DB::table('products')->whereNull('sold_by')->orWhere('sold_by', '')->update(['sold_by' => 'unit']);
        DB::table('products')->whereNull('base_unit')->orWhere('base_unit', '')->update(['base_unit' => 'pcs']);
        DB::table('sale_items')->whereNull('unit_label')->orWhere('unit_label', '')->update(['unit_label' => 'pcs']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_portions');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sold_by')) {
                $table->dropColumn('sold_by');
            }
            if (Schema::hasColumn('products', 'base_unit')) {
                $table->dropColumn('base_unit');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'unit_label')) {
                $table->dropColumn('unit_label');
            }
            if (Schema::hasColumn('sale_items', 'portion_label')) {
                $table->dropColumn('portion_label');
            }
        });

        // Revert DECIMAL back to INT (data precision beyond integers will be truncated)
        DB::statement('ALTER TABLE `products` MODIFY COLUMN `quantity` INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `products` MODIFY COLUMN `low_stock_threshold` INT NOT NULL DEFAULT 5');
        DB::statement('ALTER TABLE `sale_items` MODIFY COLUMN `quantity` INT NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `inventory_logs` MODIFY COLUMN `change_amount` INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `purchase_order_items` MODIFY COLUMN `quantity` INT NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE `quotation_items` MODIFY COLUMN `quantity` INT NOT NULL DEFAULT 1');
    }
};
