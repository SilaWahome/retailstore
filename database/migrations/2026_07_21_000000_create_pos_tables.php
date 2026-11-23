<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('code', 10);
                $table->string('symbol', 10);
                $table->decimal('exchange_rate', 15, 4)->default(1.0000);
                $table->boolean('is_base')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('category')->default('General');
                $table->string('sku')->index();
                $table->string('image_path')->nullable();
                $table->decimal('price', 15, 2);
                $table->decimal('cost', 15, 2);
                $table->integer('quantity')->default(0);
                $table->integer('low_stock_threshold')->default(5);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('po_number');
                $table->string('supplier_name');
                $table->string('status')->default('pending'); // pending, received, cancelled
                $table->decimal('total_cost', 15, 2)->default(0.00);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->integer('quantity');
                $table->decimal('cost', 15, 2);
            });
        }

        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('customer_name')->nullable();
                $table->decimal('subtotal', 15, 2);
                $table->decimal('discount', 15, 2)->default(0.00);
                $table->decimal('tax', 15, 2)->default(0.00);
                $table->decimal('total', 15, 2);
                $table->string('currency_code', 10);
                $table->decimal('exchange_rate', 15, 4)->default(1.0000);
                $table->decimal('amount_paid', 15, 2);
                $table->decimal('change_amount', 15, 2)->default(0.00);
                $table->string('payment_method')->default('cash'); // cash, card, mobile
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->integer('quantity');
                $table->decimal('price', 15, 2);
                $table->decimal('total', 15, 2);
            });
        }

        if (!Schema::hasTable('inventory_logs')) {
            Schema::create('inventory_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('change_amount');
                $table->string('type'); // sale, purchase_order, adjustment
                $table->string('description')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('quotations')) {
            Schema::create('quotations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('provider_name');
                $table->string('quote_number');
                $table->string('status')->default('pending'); // pending, approved, expired
                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->date('valid_until');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quotation_items')) {
            Schema::create('quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
                $table->string('product_name');
                $table->integer('quantity');
                $table->decimal('price', 15, 2);
            });
        }

        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('amount', 15, 2);
                $table->string('category'); // rent, utilities, supplies, other
                $table->string('description')->nullable();
                $table->date('expense_date');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('currencies');
    }
};
