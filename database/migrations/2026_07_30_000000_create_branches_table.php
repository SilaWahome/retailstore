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
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('code', 50)->nullable();
                $table->string('address')->nullable();
                $table->string('phone', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default 'Main Branch' for any existing tenants
            $tenants = DB::table('tenants')->get();
            foreach ($tenants as $tenant) {
                DB::table('branches')->insert([
                    'tenant_id' => $tenant->id,
                    'name' => 'Main Branch',
                    'code' => 'MAIN',
                    'address' => $tenant->location ?? 'Main Headquarters',
                    'phone' => $tenant->phone ?? null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
