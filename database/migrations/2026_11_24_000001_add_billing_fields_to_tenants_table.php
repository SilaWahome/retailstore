<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'monthly_fee_ugx')) {
                $table->decimal('monthly_fee_ugx', 12, 2)->default(20000.00)->after('current_plan_id');
            }
            if (!Schema::hasColumn('tenants', 'pending_bill_ugx')) {
                $table->decimal('pending_bill_ugx', 12, 2)->default(0.00)->after('monthly_fee_ugx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'pending_bill_ugx')) {
                $table->dropColumn('pending_bill_ugx');
            }
            if (Schema::hasColumn('tenants', 'monthly_fee_ugx')) {
                $table->dropColumn('monthly_fee_ugx');
            }
        });
    }
};
