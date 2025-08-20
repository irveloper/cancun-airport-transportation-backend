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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('to_location_id')->constrained('locations')->onDelete('cascade');
            $table->string('estimated_time')->nullable(); // e.g., "1 hour and 30 minutes"
            $table->decimal('distance_km', 8, 2)->nullable(); // distance in kilometers
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Evitar rutas duplicadas
            $table->unique(['from_location_id', 'to_location_id']);
            $table->index(['active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
