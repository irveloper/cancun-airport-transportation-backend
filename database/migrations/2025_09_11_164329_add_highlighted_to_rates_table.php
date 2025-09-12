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
        Schema::table('rates', function (Blueprint $table) {
            $table->boolean('highlighted')->default(false)->after('available');
            $table->text('highlight_description')->nullable()->after('highlighted');
            $table->string('highlight_badge')->nullable()->after('highlight_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn(['highlighted', 'highlight_description', 'highlight_badge']);
        });
    }
};
