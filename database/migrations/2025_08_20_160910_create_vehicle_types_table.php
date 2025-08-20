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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // standard private, vip private, limousines
            $table->string('code'); // ES, VP, LS
            $table->string('image')->nullable(); // van.png, suburban.png
            $table->text('details')->nullable(); // HTML details in English
            $table->text('detalles')->nullable(); // HTML details in Spanish
            $table->integer('max_units'); // mUnits - maximum vehicles available
            $table->integer('max_pax'); // mPax - maximum passengers
            $table->string('travel_time')->nullable(); // timeFromAirport
            $table->string('video_url')->nullable(); // video URL
            $table->string('frame')->nullable(); // iframe path
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
