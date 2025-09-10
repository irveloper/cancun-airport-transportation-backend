<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Quote extends Model
{
    protected $fillable = [
        'quote_number',
        'service_type_id',
        'vehicle_type_id',
        'from_location_id',
        'to_location_id',
        'pax',
        'service_date',
        'cost_vehicle_one_way',
        'total_one_way',
        'cost_vehicle_round_trip',
        'total_round_trip',
        'num_vehicles',
        'currency',
        'exchange_rate',
        'additional_data',
        'status',
        'expires_at',
        'customer_email',
        'customer_phone',
        'customer_name',
        'pricing_breakdown',
        'features',
    ];

    protected $casts = [
        'service_date' => 'date',
        'expires_at' => 'datetime',
        'cost_vehicle_one_way' => 'decimal:2',
        'total_one_way' => 'decimal:2',
        'cost_vehicle_round_trip' => 'decimal:2',
        'total_round_trip' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'additional_data' => 'array',
        'pricing_breakdown' => 'array',
        'features' => 'array',
    ];

    // Relationships
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(function ($query) {
                         $query->whereNull('expires_at')
                               ->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Mutators & Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getServiceTypeNameAttribute(): string
    {
        return $this->serviceType->name ?? '';
    }

    public function getVehicleTypeNameAttribute(): string
    {
        return $this->vehicleType->name ?? '';
    }

    public function getFromLocationNameAttribute(): string
    {
        return $this->fromLocation->name ?? '';
    }

    public function getToLocationNameAttribute(): string
    {
        return $this->toLocation->name ?? '';
    }

    // Helper methods
    public static function generateQuoteNumber(): string
    {
        $prefix = 'FS';
        $timestamp = now()->format('ymdHi');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return $prefix . $timestamp . $random;
    }

    public function calculateExpirationDate(): Carbon
    {
        // Quotes expire after 24 hours by default
        return now()->addHours(24);
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);
    }

    public function markAsBooked(): bool
    {
        return $this->update([
            'status' => 'booked',
        ]);
    }

    public function getFormattedPrice(string $type = 'one_way'): string
    {
        $price = $type === 'round_trip' ? $this->total_round_trip : $this->total_one_way;
        return number_format($price, 2, '.', '');
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'quote_number' => $this->quote_number,
            'service_type' => $this->serviceType->code ?? null,
            'service_type_name' => $this->service_type_name,
            'vehicle_type_name' => $this->vehicle_type_name,
            'from_location' => $this->from_location_name,
            'to_location' => $this->to_location_name,
            'pax' => $this->pax,
            'service_date' => $this->service_date->format('Y-m-d'),
            'pricing' => [
                'one_way' => [
                    'cost' => $this->getFormattedPrice('one_way'),
                    'total' => (int) $this->total_one_way,
                ],
                'round_trip' => [
                    'cost' => $this->cost_vehicle_round_trip ? $this->getFormattedPrice('round_trip') : null,
                    'total' => $this->total_round_trip ? (int) $this->total_round_trip : null,
                ],
            ],
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->is_expired,
            'features' => $this->features,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}