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
        if (!Schema::hasTable('iotec_transactions')) {
            Schema::create('iotec_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
                $table->string('type', 30); // 'collection', 'disbursement'
                $table->string('network', 30)->default('MTN'); // 'MTN', 'Airtel'
                $table->string('phone_number', 50);
                $table->decimal('amount', 15, 2); // Requested UGX amount
                $table->decimal('fee', 15, 2)->default(0.00); // 500 UGX for collection, 5% for withdrawal
                $table->decimal('net_amount', 15, 2)->default(0.00);
                $table->string('currency', 10)->default('UGX');
                $table->string('status', 30)->default('pending'); // 'pending', 'complete', 'failed'
                $table->string('external_id', 100)->unique();
                $table->string('iotec_request_id', 100)->nullable();
                $table->string('iotec_transaction_id', 100)->nullable();
                $table->string('payer_or_payee_name', 150)->nullable();
                $table->text('notes')->nullable();
                $table->text('error_message')->nullable();
                $table->json('response_data')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iotec_transactions');
    }
};
