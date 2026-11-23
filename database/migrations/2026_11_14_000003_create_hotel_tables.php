<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('base_price_ugx', 12, 2);
            $table->integer('capacity')->default(2);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->string('floor', 20)->nullable();
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning', 'maintenance'])->default('available');
            $table->string('housekeeper_note')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('guest_name', 150);
            $table->string('guest_phone', 50);
            $table->string('guest_id_number', 100)->nullable();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->decimal('nightly_rate_ugx', 12, 2);
            $table->enum('status', ['confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guest_folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->decimal('total_charges_ugx', 12, 2)->default(0.00);
            $table->decimal('total_paid_ugx', 12, 2)->default(0.00);
            $table->boolean('is_settled')->default(false);
            $table->timestamps();
        });

        Schema::create('folio_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('guest_folios')->cascadeOnDelete();
            $table->enum('source', ['room_rate', 'restaurant_pos', 'laundry', 'bar', 'extra_bed', 'other']);
            $table->string('reference_id', 50)->nullable();
            $table->string('description', 255);
            $table->decimal('amount_ugx', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('folio_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('guest_folios')->cascadeOnDelete();
            $table->decimal('amount_ugx', 12, 2);
            $table->string('payment_method', 40);
            $table->string('reference', 100)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_payments');
        Schema::dropIfExists('folio_charges');
        Schema::dropIfExists('guest_folios');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
    }
};

