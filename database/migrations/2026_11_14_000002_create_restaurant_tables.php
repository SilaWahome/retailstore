<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('dining_areas')->cascadeOnDelete();
            $table->string('table_number', 20);
            $table->integer('seating_capacity')->default(4);
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning'])->default('available');
            $table->unsignedBigInteger('current_order_id')->nullable();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price_ugx', 12, 2);
            $table->enum('station', ['kitchen', 'bar', 'pastry'])->default('kitchen');
            $table->string('printer_ip', 45)->nullable();
            $table->string('color_tag', 30)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->integer('min_selections')->default(0);
            $table->integer('max_selections')->default(1);
            $table->timestamps();
        });

        Schema::create('menu_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('menu_modifier_groups')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('extra_price', 10, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('menu_item_modifier_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('modifier_group_id')->constrained('menu_modifier_groups')->cascadeOnDelete();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('unit', 30)->default('pcs');
            $table->decimal('stock_qty', 12, 3)->default(0);
            $table->decimal('low_stock_threshold', 12, 3)->default(0);
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('menu_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity_required', 12, 3);
            $table->timestamps();
        });

        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('dining_tables')->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('guest_count')->default(1);
            $table->enum('status', ['open', 'bill_printed', 'paid', 'posted_to_room', 'cancelled'])->default('open');
            $table->decimal('subtotal_ugx', 12, 2)->default(0);
            $table->decimal('tax_ugx', 12, 2)->default(0);
            $table->decimal('total_amount_ugx', 12, 2)->default(0);
            $table->decimal('paid_amount_ugx', 12, 2)->default(0);
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_status', 30)->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('restaurant_orders')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('item_name', 150);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('modifiers_total', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->integer('seat_number')->default(1);
            $table->json('modifiers')->nullable();
            $table->text('special_notes')->nullable();
            $table->enum('status', ['pending_fire', 'fired', 'in_prep', 'ready', 'served'])->default('pending_fire');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });

        Schema::create('kitchen_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('restaurant_orders')->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('dining_tables')->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('station', ['kitchen', 'bar', 'pastry'])->default('kitchen');
            $table->enum('status', ['pending', 'in_prep', 'ready', 'served'])->default('pending');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kitchen_ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('kitchen_tickets')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('restaurant_order_items')->nullOnDelete();
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->string('item_name', 150);
            $table->integer('quantity');
            $table->text('modifiers_summary')->nullable();
            $table->text('special_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('station_printers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('station', ['kitchen', 'bar', 'pastry']);
            $table->string('printer_name', 100);
            $table->string('printer_ip', 45)->nullable();
            $table->integer('printer_port')->default(9100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'station']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_printers');
        Schema::dropIfExists('kitchen_ticket_items');
        Schema::dropIfExists('kitchen_tickets');
        Schema::dropIfExists('restaurant_order_items');
        Schema::dropIfExists('restaurant_orders');
        Schema::dropIfExists('menu_recipes');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('menu_item_modifier_group');
        Schema::dropIfExists('menu_modifiers');
        Schema::dropIfExists('menu_modifier_groups');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('dining_tables');
        Schema::dropIfExists('dining_areas');
    }
};

