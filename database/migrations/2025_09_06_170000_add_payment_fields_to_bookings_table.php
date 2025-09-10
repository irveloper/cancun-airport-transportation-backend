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
        Schema::table('bookings', function (Blueprint $table) {
            // Stripe payment fields
            $table->string('stripe_payment_intent_id')->nullable()->after('quote_id');
            $table->string('stripe_charge_id')->nullable()->after('stripe_payment_intent_id');
            
            // Payment status and tracking
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'canceled', 'refunded'])
                  ->default('pending')
                  ->after('stripe_charge_id');
            $table->timestamp('payment_date')->nullable()->after('payment_status');
            $table->text('payment_failure_reason')->nullable()->after('payment_date');
            
            // Indexes for payment queries
            $table->index(['stripe_payment_intent_id']);
            $table->index(['payment_status']);
            $table->index(['payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['stripe_payment_intent_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_date']);
            
            $table->dropColumn([
                'stripe_payment_intent_id',
                'stripe_charge_id', 
                'payment_status',
                'payment_date',
                'payment_failure_reason'
            ]);
        });
    }
};