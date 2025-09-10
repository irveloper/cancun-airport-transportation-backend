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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            
            // Customer and service information
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_type_id')->constrained();
            $table->foreignId('vehicle_type_id')->nullable()->constrained();
            $table->string('service_name');
            
            // Location information
            $table->foreignId('from_location_id')->constrained('locations');
            $table->foreignId('to_location_id')->constrained('locations');
            $table->string('pickup_location');
            $table->string('dropoff_location');
            $table->enum('from_location_type', ['airport', 'location', 'zone']);
            $table->enum('to_location_type', ['airport', 'location', 'zone']);
            
            // Trip details
            $table->enum('trip_type', ['arrival', 'departure', 'round-trip', 'hotel-to-hotel']);
            $table->datetime('pickup_date_time');
            $table->integer('passengers');
            $table->integer('child_seats')->default(0);
            $table->boolean('wheelchair_accessible')->default(false);
            
            // Pricing
            $table->string('currency', 3)->default('USD');
            $table->decimal('total_price', 10, 2);
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            // Flight information (JSON for flexibility)
            $table->json('arrival_flight_info')->nullable();
            $table->json('departure_flight_info')->nullable();
            
            // Additional details
            $table->text('special_requests')->nullable();
            $table->string('hotel_reservation_name')->nullable();
            $table->date('booking_date');
            
            // Status and tracking
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Reference to original quote if exists
            $table->foreignId('quote_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['booking_number']);
            $table->index(['status']);
            $table->index(['trip_type']);
            $table->index(['pickup_date_time']);
            $table->index(['booking_date']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
