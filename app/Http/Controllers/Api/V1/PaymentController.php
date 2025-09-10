<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
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

        if (!$endpointSecret) {
            Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook not properly configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload', [
                'error' => $e->getMessage(),
                'payload_length' => strlen($payload),
                'signature_header' => $sigHeader ? 'present' : 'missing'
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', [
                'error' => $e->getMessage(),
                'signature_header' => $sigHeader,
                'payload_length' => strlen($payload)
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'event_type' => $event['type'],
            'event_id' => $event['id'],
            'event_created' => $event['created'] ?? null,
            'livemode' => $event['livemode'] ?? null,
            'object_id' => $event['data']['object']['id'] ?? null
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
                
            case 'payment_intent.created':
                $this->handlePaymentCreated($event['data']['object']);
                break;
                
            case 'payment_intent.requires_action':
                $this->handlePaymentRequiresAction($event['data']['object']);
                break;
                
            case 'payment_intent.processing':
                $this->handlePaymentProcessing($event['data']['object']);
                break;
                
            case 'payment_intent.amount_capturable_updated':
                $this->handleAmountCapturableUpdated($event['data']['object']);
                break;
                
            case 'payment_intent.partially_funded':
                $this->handlePaymentPartiallyFunded($event['data']['object']);
                break;
                
            default:
                Log::info('Unhandled Stripe webhook event type', [
                    'type' => $event['type'],
                    'event_id' => $event['id'],
                    'object_id' => $event['data']['object']['id'] ?? null
                ]);
        }

        return response()->json([
            'status' => 'success',
            'event_id' => $event['id'],
            'processed_at' => now()->toISOString()
        ]);
    }

    /**
     * Helper method to find booking by payment intent ID with detailed logging
     */
    private function findBookingByPaymentIntent(string $paymentIntentId): ?Booking
    {
        $booking = Booking::where('stripe_payment_intent_id', $paymentIntentId)
                         ->with(['customer', 'serviceType', 'vehicleType'])
                         ->first();

        if (!$booking) {
            Log::warning('Booking not found for payment intent', [
                'payment_intent_id' => $paymentIntentId,
                'total_bookings_in_db' => Booking::count(),
                'bookings_with_payment_intents' => Booking::whereNotNull('stripe_payment_intent_id')->count()
            ]);
        }

        return $booking;
    }

    /**
     * Helper method to log payment intent details consistently
     */
    private function logPaymentIntentDetails(array $paymentIntent, string $context = ''): array
    {
        return [
            'context' => $context,
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => $paymentIntent['amount'] ?? null,
            'currency' => $paymentIntent['currency'] ?? null,
            'status' => $paymentIntent['status'] ?? null,
            'client_secret_exists' => isset($paymentIntent['client_secret']),
            'payment_method_types' => $paymentIntent['payment_method_types'] ?? [],
            'latest_charge' => $paymentIntent['latest_charge'] ?? null,
            'cancellation_reason' => $paymentIntent['cancellation_reason'] ?? null,
            'created' => $paymentIntent['created'] ?? null
        ];
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($paymentIntent): void
    {
        try {
            $booking = $this->findBookingByPaymentIntent($paymentIntent['id']);
            
            if (!$booking) {
                Log::warning('Booking not found for successful payment intent', 
                    $this->logPaymentIntentDetails($paymentIntent, 'payment_succeeded')
                );
                return;
            }

            // Prevent duplicate processing
            if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
                Log::info('Payment already processed for booking', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
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
                'payment_failure_reason' => null, // Clear any previous failure reason
            ]);

            Log::info('Booking confirmed via Stripe payment', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'customer_email' => $booking->customer->email ?? null,
                'payment_intent_id' => $paymentIntent['id'],
                'charge_id' => $paymentIntent['latest_charge'],
                'amount' => $paymentIntent['amount'],
                'currency' => $paymentIntent['currency'],
                'payment_method' => $paymentIntent['payment_method_types'][0] ?? null
            ]);

            // Send confirmation email to customer
            $this->sendBookingConfirmationEmail($booking);

        } catch (\Exception $e) {
            Log::error('Error handling successful payment webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_data' => [
                    'amount' => $paymentIntent['amount'] ?? null,
                    'currency' => $paymentIntent['currency'] ?? null,
                    'status' => $paymentIntent['status'] ?? null
                ]
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
                    'payment_intent_id' => $paymentIntent['id'],
                    'amount' => $paymentIntent['amount'],
                    'currency' => $paymentIntent['currency']
                ]);
                return;
            }

            $failureReason = null;
            $failureCode = null;
            
            if (isset($paymentIntent['last_payment_error'])) {
                $failureReason = $paymentIntent['last_payment_error']['message'] ?? 'Payment failed';
                $failureCode = $paymentIntent['last_payment_error']['code'] ?? null;
            }

            $booking->update([
                'payment_status' => 'failed',
                'payment_failure_reason' => $failureReason ?? 'Payment failed - no specific reason provided'
            ]);

            Log::warning('Booking payment failed', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'customer_email' => $booking->customer->email ?? null,
                'payment_intent_id' => $paymentIntent['id'],
                'failure_reason' => $failureReason,
                'failure_code' => $failureCode,
                'decline_code' => $paymentIntent['last_payment_error']['decline_code'] ?? null,
                'payment_method' => $paymentIntent['payment_method_types'][0] ?? null,
                'amount' => $paymentIntent['amount'],
                'currency' => $paymentIntent['currency']
            ]);

            // Here you could:
            // - Send email notification to customer about failed payment
            // - Provide retry payment link
            // - Alert operations team for manual follow-up
            // - Set booking expiry timer

        } catch (\Exception $e) {
            Log::error('Error handling failed payment webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                    'payment_intent_id' => $paymentIntent['id'],
                    'amount' => $paymentIntent['amount'],
                    'currency' => $paymentIntent['currency']
                ]);
                return;
            }

            $booking->update([
                'payment_status' => 'canceled',
                'payment_failure_reason' => 'Payment was canceled by customer or expired'
            ]);

            Log::info('Booking payment canceled', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'customer_email' => $booking->customer->email ?? null,
                'payment_intent_id' => $paymentIntent['id'],
                'amount' => $paymentIntent['amount'],
                'currency' => $paymentIntent['currency'],
                'cancellation_reason' => $paymentIntent['cancellation_reason'] ?? 'unknown'
            ]);

            // Here you could:
            // - Send email notification about cancellation
            // - Release held inventory/capacity
            // - Set booking status to expired/canceled
            // - Provide option to restart payment

        } catch (\Exception $e) {
            Log::error('Error handling canceled payment webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle payment intent created
     */
    private function handlePaymentCreated($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::info('Payment intent created for unknown booking', [
                    'payment_intent_id' => $paymentIntent['id'],
                    'amount' => $paymentIntent['amount'],
                    'currency' => $paymentIntent['currency']
                ]);
                return;
            }

            // Update payment status to pending if not already set
            if ($booking->payment_status === null) {
                $booking->update([
                    'payment_status' => 'pending'
                ]);
            }

            Log::info('Payment intent created', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id'],
                'amount' => $paymentIntent['amount'],
                'currency' => $paymentIntent['currency'],
                'status' => $paymentIntent['status']
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling payment intent created webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle payment requires action (3D Secure, etc.)
     */
    private function handlePaymentRequiresAction($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::warning('Payment requires action for unknown booking', [
                    'payment_intent_id' => $paymentIntent['id']
                ]);
                return;
            }

            $booking->update([
                'payment_status' => 'requires_action',
                'payment_failure_reason' => 'Additional authentication required'
            ]);

            Log::info('Payment requires action', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id'],
                'next_action_type' => $paymentIntent['next_action']['type'] ?? null
            ]);

            // Here you could:
            // - Send email/SMS to customer about required action
            // - Update frontend status
            // - Set expiry timer

        } catch (\Exception $e) {
            Log::error('Error handling payment requires action webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle payment processing
     */
    private function handlePaymentProcessing($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::warning('Payment processing for unknown booking', [
                    'payment_intent_id' => $paymentIntent['id']
                ]);
                return;
            }

            $booking->update([
                'payment_status' => 'processing'
            ]);

            Log::info('Payment is processing', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id'],
                'processing_method' => $paymentIntent['payment_method_types'][0] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling payment processing webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle amount capturable updated (for delayed capture)
     */
    private function handleAmountCapturableUpdated($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::warning('Amount capturable updated for unknown booking', [
                    'payment_intent_id' => $paymentIntent['id']
                ]);
                return;
            }

            Log::info('Amount capturable updated', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id'],
                'amount_capturable' => $paymentIntent['amount_capturable'],
                'original_amount' => $paymentIntent['amount']
            ]);

            // Here you could:
            // - Update booking amount if needed
            // - Notify operations team for manual capture decision
            // - Auto-capture based on business rules

        } catch (\Exception $e) {
            Log::error('Error handling amount capturable updated webhook', [
                'payment_intent_id' => $paymentIntent['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle partially funded payment (for delayed payment methods like bank transfers)
     */
    private function handlePaymentPartiallyFunded($paymentIntent): void
    {
        try {
            $booking = Booking::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
            
            if (!$booking) {
                Log::warning('Payment partially funded for unknown booking', [
                    'payment_intent_id' => $paymentIntent['id']
                ]);
                return;
            }

            $booking->update([
                'payment_status' => 'partially_funded'
            ]);

            Log::info('Payment partially funded', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent['id'],
                'amount_received' => $paymentIntent['amount_received'] ?? null,
                'total_amount' => $paymentIntent['amount']
            ]);

            // Here you could:
            // - Send notification about partial payment
            // - Set up monitoring for remaining amount
            // - Update booking status to reflect partial payment

        } catch (\Exception $e) {
            Log::error('Error handling payment partially funded webhook', [
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

    /**
     * Create payment intent for existing booking (by ID or booking number)
     */
    public function createPaymentIntentForBooking(Request $request, $identifier): JsonResponse
    {
        try {
            // Find booking by ID or booking number
            $booking = $this->findBookingByIdentifier($identifier);
            
            if (!$booking) {
                return $this->errorResponse('Booking not found with ID or booking number: ' . $identifier, 404);
            }

            // Check if booking is in correct state for payment
            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                return $this->errorResponse('Booking is not available for payment. Status: ' . $booking->status, 422);
            }

            // Check if payment is already completed
            if ($booking->payment_status === 'paid') {
                return $this->errorResponse('This booking is already paid', 422);
            }

            // Validate currency if provided, otherwise use booking currency
            $currency = $request->input('currency', $booking->currency ?? 'USD');
            
            $validator = Validator::make(['currency' => $currency], [
                'currency' => 'required|string|size:3|in:USD,EUR,GBP,MXN,CAD',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Check if payment intent already exists and is still valid
            if ($booking->stripe_payment_intent_id) {
                try {
                    $existingIntent = PaymentIntent::retrieve($booking->stripe_payment_intent_id);
                    
                    if (in_array($existingIntent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'])) {
                        return $this->successResponse([
                            'client_secret' => $existingIntent->client_secret,
                            'payment_intent_id' => $existingIntent->id,
                            'amount' => $existingIntent->amount,
                            'currency' => $existingIntent->currency,
                            'booking' => $booking->toApiResponse(),
                            'reused_existing' => true
                        ], 'Existing payment intent retrieved');
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve existing payment intent, creating new one', [
                        'booking_id' => $booking->id,
                        'intent_id' => $booking->stripe_payment_intent_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Convert amount to cents (Stripe requires amounts in cents)
            $amount = (int)($booking->total_price * 100);
            $stripeCurrency = strtolower($currency);

            // Create new payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $stripeCurrency,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_email' => $booking->customer->email,
                    'service_type' => $booking->service_name,
                    'pickup_location' => $booking->pickup_location,
                    'dropoff_location' => $booking->dropoff_location,
                    'created_from' => 'frontend_search'
                ],
                'description' => "FiveStars Transfer - {$booking->booking_number}",
                'receipt_email' => $booking->customer->email,
            ]);

            // Update booking with new payment intent ID
            $booking->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_status' => 'pending',
                'currency' => $currency // Update currency if different
            ]);

            Log::info('Payment intent created for existing booking', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $stripeCurrency,
                'search_identifier' => $identifier
            ]);

            return $this->successResponse([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $stripeCurrency,
                'booking' => $booking->toApiResponse(),
                'reused_existing' => false,
                'expires_at' => now()->addHour()->toISOString()
            ], 'Payment intent created successfully', 201);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error creating payment intent for booking', [
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
                'identifier' => $identifier
            ]);

            return $this->errorResponse('Payment processing error: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            Log::error('Error creating payment intent for booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'identifier' => $identifier
            ]);

            return $this->errorResponse('Failed to create payment intent', 500);
        }
    }

    /**
     * Find booking by ID or booking number
     */
    private function findBookingByIdentifier($identifier)
    {
        $booking = null;
        
        if (is_numeric($identifier)) {
            // Search by ID first
            $booking = Booking::with(['customer', 'serviceType', 'vehicleType', 'fromLocation', 'toLocation'])
                             ->find($identifier);
        }
        
        // If not found by ID or if identifier is not numeric, try booking_number
        if (!$booking) {
            $booking = Booking::with(['customer', 'serviceType', 'vehicleType', 'fromLocation', 'toLocation'])
                             ->where('booking_number', $identifier)
                             ->first();
        }
        
        return $booking;
    }

    /**
     * Send booking confirmation email to customer
     */
    private function sendBookingConfirmationEmail(Booking $booking): void
    {
        try {
            if ($booking->customer && $booking->customer->email) {
                Mail::to($booking->customer->email)->send(new \App\Mail\BookingConfirmation($booking));
                
                Log::info('Booking confirmation email queued', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_email' => $booking->customer->email
                ]);
            } else {
                Log::warning('Cannot send booking confirmation email - missing customer email', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation email', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}