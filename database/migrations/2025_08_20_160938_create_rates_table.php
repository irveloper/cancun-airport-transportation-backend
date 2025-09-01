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
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained()->onDelete('cascade');
            
            // Zone-based pricing (primary method)
            $table->foreignId('from_zone_id')->nullable()->constrained('zones')->onDelete('cascade');
            $table->foreignId('to_zone_id')->nullable()->constrained('zones')->onDelete('cascade');
            
            // Location-specific pricing (for overrides)
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->onDelete('cascade');
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->onDelete('cascade');
            
            $table->decimal('cost_vehicle_one_way', 10, 2); // costVehicleOW
            $table->decimal('total_one_way', 10, 2); // totalOW
            $table->decimal('cost_vehicle_round_trip', 10, 2); // costVehicleRT
            $table->decimal('total_round_trip', 10, 2); // totalRT
            $table->integer('num_vehicles')->default(1); // numVehicles
            $table->boolean('available')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->index(['service_type_id', 'from_zone_id', 'to_zone_id']);
            $table->index(['service_type_id', 'from_location_id', 'to_location_id']);
            $table->index(['available', 'valid_from', 'valid_to']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
