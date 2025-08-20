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
        Schema::create('service_features', function (Blueprint $table) {
            $table->id();
            $table->string('name_en'); // English name
            $table->string('name_es'); // Spanish name
            $table->text('description_en')->nullable(); // English description
            $table->text('description_es')->nullable(); // Spanish description
            $table->string('icon')->nullable(); // Icon class or image
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0); // For ordering features
            $table->timestamps();
            
            $table->index(['active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_features');
    }
};
