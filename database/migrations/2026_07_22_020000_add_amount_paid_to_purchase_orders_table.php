<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->default(0.00)->after('total_cost');
            }
            if (!Schema::hasColumn('purchase_orders', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('amount_paid'); // unpaid, partial, paid
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_status']);
        });
    }
};
