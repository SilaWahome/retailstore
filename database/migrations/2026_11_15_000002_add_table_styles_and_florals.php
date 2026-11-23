<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `dining_tables` MODIFY COLUMN `shape` VARCHAR(30) NOT NULL DEFAULT 'square'");
            DB::statement("ALTER TABLE `dining_zones` MODIFY COLUMN `zone_type` VARCHAR(30) NOT NULL DEFAULT 'counter'");
        }

        Schema::table('dining_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('dining_tables', 'has_floral')) {
                $table->boolean('has_floral')->default(false)->after('shape');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dining_tables', function (Blueprint $table) {
            if (Schema::hasColumn('dining_tables', 'has_floral')) {
                $table->dropColumn('has_floral');
            }
        });
    }
};

