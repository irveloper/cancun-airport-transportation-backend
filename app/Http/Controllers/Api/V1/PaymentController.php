<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class PaymentController extends BaseApiController
{
    public function __construct()
    {
        // Set Stripe secret key
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for a booking
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'booking_id' => 'required|exists:bookings,id',
                'currency' => 'required|string|size:3|in:USD,EUR,GBP,MXN,CAD',
            ]);

            $booking = Booking::with(['customer', 'serviceType', 'fromLocation', 'toLocation'])
                            ->findOrFail($validated['booking_id']);

            // Check if booking is in correct state for payment
            if ($booking->status !== 'pending') {
                return $this->errorResponse('Booking is not available for payment', 422);
            }

            // Check if payment intent already exists
            if ($booking->stripe_payment_intent_id) {
                try {
                    $existingIntent = PaymentIntent::retrieve($booking->stripe_payment_intent_id);
                    
                    if ($existingIntent->status === 'requires_payment_method' || 
                        $existingIntent->status === 'requires_confirmation') {
                        
                        return $this->successResponse([
                            'client_secret' => $existingIntent->client_secret,
                            'payment_intent_id' => $existingIntent->id,
                            'amount' => $existingIntent->amount,
                            'currency' => $existingIntent->currency,
                            'booking' => $booking->toApiResponse()
                        ], 'Existing payment intent retrieved');
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve existing payment intent', [
                        'booking_id' => $booking->id,
                        'intent_id' => $booking->stripe_payment_intent_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Convert amount to cents (Stripe requires amounts in cents)
            $amount = (int)($booking->total_price * 100);
            $currency = strtolower($validated['currency']);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_email' => $booking->customer->email,
                    'service_type' => $booking->service_name,
                    'pickup_location' => $booking->pickup_location,
                    'dropoff_location' => $booking->dropoff_location,
                ],
                'description' => "FiveStars Transfer - {$booking->booking_number}",
                'receipt_email' => $booking->customer->email,
            ]);

            // Update booking with payment intent ID
            $booking->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => 'pending'
            ]);

            Log::info('Payment intent created', [
                'booking_id' => $booking->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency
            ]);

            return $this->successResponse([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency,
                'booking' => $booking->toApiResponse()
            ], 'Payment intent created successfully');

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error creating payment intent', [
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
                'request_data' => $request->all()
            ]);

            return $this->errorResponse('Payment processing error: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            Log::error('Error creating payment intent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return $this->errorResponse('Failed to create payment intent', 500);
        }
    }

    /**
     * Handle Stripe webhooks for payment confirmation
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'event_type' => $event['type'],
            'event_id' => $event['id']
        ]);

        // Handle the event
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event['data']['object']);
                break;
                
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event['data']['object']);
                break;
                
            case 'payment_intent.canceled':
                $this->handlePaymentCanceled($event['data']['object']);
                break;
                
            default:
                Log::info('Unhandled Stripe webhook event type', ['type' => $event['type']]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::warning('Booking not found for payment intent', [
                    'payment_intent_id' => $paymentIntent['id']
                ]);
                return;
            }

            // Update booking status to confirmed and paid
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_date' => now(),
                'stripe_charge_id' => $paymentIntent['latest_charge'] ?? null,
            ]);

            Log::info('Booking confirmed via Stripe payment', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id'],
                'amount' => $paymentIntent['amount']
            ]);

            // Here you could also:
            // - Send confirmation email to customer
            // - Notify driver/operations team
            // - Update external systems

        } catch (\Exception $e) {
            Log::error('Error handling successful payment webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::warning('Booking not found for failed payment intent', [
                    'payment_intent_id' => $paymentIntent['id']
                ]);
                return;
            }

            $booking->update([
                'payment_status' => 'failed',
                'payment_failure_reason' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed'
            ]);

            Log::warning('Booking payment failed', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id'],
                'reason' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling failed payment webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle canceled payment
     */
    private function handlePaymentCanceled($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::warning('Booking not found for canceled payment intent', [
                    'payment_intent_id' => $paymentIntent['id']
                ]);
                return;
            }

            $booking->update([
                'payment_status' => 'canceled'
            ]);

            Log::info('Booking payment canceled', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id']
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling canceled payment webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get payment status for a booking
     */
    public function getPaymentStatus($bookingId): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($bookingId);

            $response = [
                'booking_id' => $booking->id,
                'payment_status' => $booking->payment_status,
                'payment_intent_id' => $booking->stripe_payment_intent_id,
                'payment_date' => $booking->payment_date
            ];

            // If we have a payment intent, get current status from Stripe
            if ($booking->stripe_payment_intent_id) {
                try {
                    $paymentIntent = PaymentIntent::retrieve($booking->stripe_payment_intent_id);
                    $response['stripe_status'] = $paymentIntent->status;
                } catch (\Exception $e) {
                    Log::warning('Could not retrieve payment intent status', [
                        'booking_id' => $booking->id,
                        'payment_intent_id' => $booking->stripe_payment_intent_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $this->successResponse($response, 'Payment status retrieved');

        } catch (\Exception $e) {
            Log::error('Error getting payment status', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to get payment status', 500);
        }
    }
}