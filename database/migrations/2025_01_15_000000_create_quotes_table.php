<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('service_type_id')->constrained();
            $table->foreignId('vehicle_type_id')->constrained();
            $table->foreignId('from_location_id')->constrained('locations');
            $table->foreignId('to_location_id')->constrained('locations');
            $table->integer('pax');
            $table->date('service_date');
            
            // Quote details
            $table->decimal('cost_vehicle_one_way', 10, 2);
            $table->decimal('total_one_way', 10, 2);
            $table->decimal('cost_vehicle_round_trip', 10, 2)->nullable();
            $table->decimal('total_round_trip', 10, 2)->nullable();
            $table->integer('num_vehicles')->default(1);
            
            // Additional quote data
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->json('additional_data')->nullable();
            
            // Status and metadata
            $table->enum('status', ['draft', 'active', 'expired', 'booked'])->default('draft');
            $table->timestamp('expires_at')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_name')->nullable();
            
            // Pricing breakdown
            $table->json('pricing_breakdown')->nullable();
            $table->json('features')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['quote_number']);
            $table->index(['status', 'expires_at']);
            $table->index(['service_date']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};