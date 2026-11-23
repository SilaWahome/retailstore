<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name', 100);
            $table->enum('business_type', ['retail', 'restaurant', 'hotel']);
            $table->decimal('price_ugx', 12, 2);
            $table->string('billing_interval', 20)->default('monthly');
            $table->unsignedInteger('room_limit')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('feature_key', 100);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'business_type')) {
                $table->enum('business_type', ['retail', 'restaurant', 'hotel'])
                    ->default('retail')
                    ->after('location');
            }
            if (!Schema::hasColumn('tenants', 'current_plan_id')) {
                $table->foreignId('current_plan_id')
                    ->nullable()
                    ->after('business_type')
                    ->constrained('subscription_plans')
                    ->nullOnDelete();
            }
        });

        // Seed the 4 UGX plans and their feature flags immediately
        $now = now();
        $plans = [
            [
                'slug' => 'retail-basic',
                'name' => 'Retail Basic',
                'business_type' => 'retail',
                'price_ugx' => 25000.00,
                'billing_interval' => 'monthly',
                'room_limit' => null,
                'description' => 'Standard barcode POS, sales receipts, retail inventory, daily sales summary.',
                'features' => ['retail_pos', 'retail_inventory', 'daily_sales'],
            ],
            [
                'slug' => 'restaurant-only',
                'name' => 'Restaurant Only',
                'business_type' => 'restaurant',
                'price_ugx' => 100000.00,
                'billing_interval' => 'monthly',
                'room_limit' => null,
                'description' => 'Table floor plans, KOT printing, KDS display, split bills, menu modifiers.',
                'features' => ['table_management', 'kot_printers', 'kds_display', 'split_billing', 'menu_modifiers'],
            ],
            [
                'slug' => 'hotel-starter',
                'name' => 'Hotel Starter',
                'business_type' => 'hotel',
                'price_ugx' => 170000.00,
                'billing_interval' => 'monthly',
                'room_limit' => 15,
                'description' => 'Restaurant POS + Multi-station KOT + Recipe BOM + Room Booking & Housekeeping (up to 15 rooms).',
                'features' => [
                    'table_management', 'kot_printers', 'kds_display', 'split_billing', 'menu_modifiers',
                    'multi_station_kot', 'recipe_bom', 'room_booking', 'housekeeping_status',
                ],
            ],
            [
                'slug' => 'hotel-pro',
                'name' => 'Hotel & Resort Pro',
                'business_type' => 'hotel',
                'price_ugx' => 250000.00,
                'billing_interval' => 'monthly',
                'room_limit' => null,
                'description' => 'Unlimited rooms, Guest Folio ledger, Charge Restaurant to Room, advance calendar, audit trail.',
                'features' => [
                    'table_management', 'kot_printers', 'kds_display', 'split_billing', 'menu_modifiers',
                    'multi_station_kot', 'recipe_bom', 'room_booking', 'housekeeping_status',
                    'guest_folio', 'charge_to_room', 'advance_calendar', 'audit_trail',
                ],
            ],
        ];

        $retailPlanId = null;
        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);
            $planData['is_active'] = true;
            $planData['created_at'] = $now;
            $planData['updated_at'] = $now;

            $planId = DB::table('subscription_plans')->insertGetId($planData);
            if ($planData['slug'] === 'retail-basic') {
                $retailPlanId = $planId;
            }

            foreach ($features as $featureKey) {
                DB::table('plan_features')->insert([
                    'plan_id' => $planId,
                    'feature_key' => $featureKey,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Migrate all existing tenants to retail-basic while preserving subscription_status & subscription_ends_at
        if ($retailPlanId) {
            DB::table('tenants')
                ->whereNull('current_plan_id')
                ->update([
                    'business_type' => 'retail',
                    'current_plan_id' => $retailPlanId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'current_plan_id')) {
                $table->dropForeign(['current_plan_id']);
                $table->dropColumn('current_plan_id');
            }
            if (Schema::hasColumn('tenants', 'business_type')) {
                $table->dropColumn('business_type');
            }
        });

        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_plans');
    }
};

