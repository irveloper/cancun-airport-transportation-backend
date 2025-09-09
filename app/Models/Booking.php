<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'booking_number',
        'customer_id',
        'service_type_id',
        'vehicle_type_id',
        'service_name',
        'from_location_id',
        'to_location_id',
        'pickup_location',
        'dropoff_location',
        'from_location_type',
        'to_location_type',
        'trip_type',
        'pickup_date_time',
        'passengers',
        'child_seats',
        'wheelchair_accessible',
        'currency',
        'total_price',
        'exchange_rate',
        'arrival_flight_info',
        'departure_flight_info',
        'special_requests',
        'hotel_reservation_name',
        'booking_date',
        'status',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'quote_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'payment_status',
        'payment_date',
        'payment_failure_reason',
    ];

    protected $casts = [
        'pickup_date_time' => 'datetime',
        'booking_date' => 'date',
        'wheelchair_accessible' => 'boolean',
        'total_price' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'arrival_flight_info' => 'array',
        'departure_flight_info' => 'array',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'payment_date' => 'datetime',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTripType($query, $tripType)
    {
        return $query->where('trip_type', $tripType);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('pickup_date_time', '>', now());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('pickup_date_time', today());
    }

    // Helper methods
    public static function generateBookingNumber(): string
    {
        $prefix = 'BK';
        $timestamp = now()->format('ymdHi');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return $prefix . $timestamp . $random;
    }

    public function confirm(): bool
    {
        return $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(string $reason = null): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function markInProgress(): bool
    {
        return $this->update([
            'status' => 'in_progress',
        ]);
    }

    public function markCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
        ]);
    }

    public function isUpcoming(): bool
    {
        return $this->pickup_date_time->isFuture();
    }

    public function isPast(): bool
    {
        return $this->pickup_date_time->isPast();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && $this->isUpcoming();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function hasPaymentFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    public function requiresPayment(): bool
    {
        return $this->status === 'pending' && !$this->isPaid();
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->total_price, 2, '.', '');
    }

    public function hasArrivalFlight(): bool
    {
        return in_array($this->trip_type, ['arrival', 'round-trip']) && !empty($this->arrival_flight_info);
    }

    public function hasDepartureFlight(): bool
    {
        return in_array($this->trip_type, ['departure', 'round-trip']) && !empty($this->departure_flight_info);
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'customer' => [
                'name' => $this->customer->full_name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
                'country' => $this->customer->country,
            ],
            'service' => [
                'id' => $this->service_type_id,
                'name' => $this->service_name,
                'type' => $this->serviceType->code ?? null,
            ],
            'locations' => [
                'pickup' => [
                    'id' => $this->from_location_id,
                    'name' => $this->pickup_location,
                    'type' => $this->from_location_type,
                ],
                'dropoff' => [
                    'id' => $this->to_location_id,
                    'name' => $this->dropoff_location,
                    'type' => $this->to_location_type,
                ],
            ],
            'trip_details' => [
                'type' => $this->trip_type,
                'pickup_datetime' => $this->pickup_date_time->toISOString(),
                'passengers' => $this->passengers,
                'child_seats' => $this->child_seats,
                'wheelchair_accessible' => $this->wheelchair_accessible,
            ],
            'pricing' => [
                'total' => $this->getFormattedPrice(),
                'currency' => $this->currency,
            ],
            'payment' => [
                'status' => $this->payment_status,
                'payment_date' => $this->payment_date?->toISOString(),
                'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
                'requires_payment' => $this->requiresPayment(),
            ],
            'flight_info' => [
                'arrival' => $this->arrival_flight_info,
                'departure' => $this->departure_flight_info,
            ],
            'additional_info' => [
                'special_requests' => $this->special_requests,
                'hotel_reservation_name' => $this->hotel_reservation_name,
            ],
            'status' => $this->status,
            'booking_date' => $this->booking_date->format('Y-m-d'),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
