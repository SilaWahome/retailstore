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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'deleted_at')) {
                $table->softDeletes()->after('payment_status');
            }
            if (!Schema::hasColumn('sales', 'deletion_reason')) {
                $table->text('deletion_reason')->nullable()->after('deleted_at');
            }
            if (!Schema::hasColumn('sales', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deletion_reason');
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('sales', 'deletion_reason')) {
                $table->dropColumn('deletion_reason');
            }
            if (Schema::hasColumn('sales', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
