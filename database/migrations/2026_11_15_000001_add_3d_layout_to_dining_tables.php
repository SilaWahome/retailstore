<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dining_areas', function (Blueprint $table) {
            $table->decimal('width_m', 6, 2)->default(15.00)->after('name');
            $table->decimal('length_m', 6, 2)->default(12.33)->after('width_m');
        });

        Schema::table('dining_tables', function (Blueprint $table) {
            $table->enum('shape', ['round', 'rect'])->default('rect')->after('seating_capacity');
            $table->decimal('pos_x', 6, 2)->default(2.00)->after('shape');
            $table->decimal('pos_z', 6, 2)->default(2.00)->after('pos_x');
            $table->decimal('width_m', 5, 2)->default(1.20)->after('pos_z');
            $table->decimal('depth_m', 5, 2)->default(1.20)->after('width_m');
            $table->integer('rotation_deg')->default(0)->after('depth_m');
        });

        Schema::create('dining_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('dining_areas')->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('zone_type', ['counter', 'bar', 'kitchen', 'entrance', 'other'])->default('counter');
            $table->decimal('pos_x', 6, 2)->default(1.00);
            $table->decimal('pos_z', 6, 2)->default(1.00);
            $table->decimal('width_m', 5, 2)->default(3.50);
            $table->decimal('depth_m', 5, 2)->default(1.20);
            $table->string('color', 20)->default('#1e293b');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_zones');

        Schema::table('dining_tables', function (Blueprint $table) {
            $table->dropColumn(['shape', 'pos_x', 'pos_z', 'width_m', 'depth_m', 'rotation_deg']);
        });

        Schema::table('dining_areas', function (Blueprint $table) {
            $table->dropColumn(['width_m', 'length_m']);
        });
    }
};

