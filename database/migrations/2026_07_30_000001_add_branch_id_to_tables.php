<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'sales',
            'products',
            'expenses',
            'purchase_orders',
            'quotations',
            'inventory_logs',
            'attendances',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                });
            }
        }

        // Backfill existing records with the first branch for each tenant
        $branches = DB::table('branches')->get()->groupBy('tenant_id');

        foreach ($branches as $tenantId => $tenantBranches) {
            $mainBranch = $tenantBranches->first();
            if ($mainBranch) {
                DB::table('users')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
                DB::table('sales')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
                DB::table('products')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
                DB::table('expenses')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
                DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
                DB::table('quotations')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
                DB::table('inventory_logs')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
                DB::table('attendances')->where('tenant_id', $tenantId)->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'attendances',
            'inventory_logs',
            'quotations',
            'purchase_orders',
            'expenses',
            'products',
            'sales',
            'users',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
