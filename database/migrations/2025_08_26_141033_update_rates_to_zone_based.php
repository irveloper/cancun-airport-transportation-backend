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
        // First, update existing rates to be zone-based by populating zone IDs from locations
        DB::statement("
            UPDATE rates 
            SET from_zone_id = (
                SELECT zone_id 
                FROM locations 
                WHERE locations.id = rates.from_location_id
            )
            WHERE from_location_id IS NOT NULL
        ");
        
        DB::statement("
            UPDATE rates 
            SET to_zone_id = (
                SELECT zone_id 
                FROM locations 
                WHERE locations.id = rates.to_location_id
            )
            WHERE to_location_id IS NOT NULL
        ");

        // Now make from_zone_id and to_zone_id required, and location columns nullable
        Schema::table('rates', function (Blueprint $table) {
            // Make zone columns NOT NULL since we want zone-based rates
            $table->foreignId('from_zone_id')->nullable(false)->change();
            $table->foreignId('to_zone_id')->nullable(false)->change();
            
            // Make location columns nullable since they're now optional overrides
            $table->foreignId('from_location_id')->nullable()->change();
            $table->foreignId('to_location_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            // Revert zone columns to nullable
            $table->foreignId('from_zone_id')->nullable()->change();
            $table->foreignId('to_zone_id')->nullable()->change();
            
            // Make location columns required again
            $table->foreignId('from_location_id')->nullable(false)->change();
            $table->foreignId('to_location_id')->nullable(false)->change();
        });
        
        // Clear zone data to revert to location-based
        DB::statement("UPDATE rates SET from_zone_id = NULL, to_zone_id = NULL");
    }
};
