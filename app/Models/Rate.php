<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Rate extends Model
{
    protected $fillable = [
        'service_type_id',
        'vehicle_type_id',
        'from_zone_id',
        'to_zone_id',
        'from_location_id',
        'to_location_id',
        'cost_vehicle_one_way',
        'total_one_way',
        'cost_vehicle_round_trip',
        'total_round_trip',
        'num_vehicles',
        'available',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'available' => 'boolean',
        'cost_vehicle_one_way' => 'decimal:2',
        'total_one_way' => 'decimal:2',
        'cost_vehicle_round_trip' => 'decimal:2',
        'total_round_trip' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    // Cache TTL constants
    const CACHE_TTL = 3600; // 1 hour
    const ROUTE_CACHE_TTL = 1800; // 30 minutes

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function fromZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'from_zone_id');
    }

    public function toZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'to_zone_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    // Scope para filtrar rates válidas por fecha
    public function scopeValid($query, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        
        return $query->where('available', true)
                     ->where(function ($query) use ($date) {
                         $query->whereNull('valid_from')
                               ->orWhere('valid_from', '<=', $date);
                     })
                     ->where(function ($query) use ($date) {
                         $query->whereNull('valid_to')
                               ->orWhere('valid_to', '>=', $date);
                     });
    }

    /**
     * Scope for zone-based rates
     */
    public function scopeZoneBased($query)
    {
        return $query->whereNotNull('from_zone_id')->whereNotNull('to_zone_id');
    }

    /**
     * Scope for location-specific rates
     */
    public function scopeLocationSpecific($query)
    {
        return $query->whereNotNull('from_location_id')->whereNotNull('to_location_id');
    }

    /**
     * Find rates for a specific location-to-location route with caching
     * Priority: Zone-based rates (primary) > Location-specific rates (overrides)
     */
    public static function findForRoute($serviceTypeId, $fromLocationId, $toLocationId, $date = null)
    {
        $cacheKey = "route_rates:{$serviceTypeId}:{$fromLocationId}:{$toLocationId}:" . ($date ? Carbon::parse($date)->format('Y-m-d') : 'today');
        
        return Cache::remember($cacheKey, self::ROUTE_CACHE_TTL, function () use ($serviceTypeId, $fromLocationId, $toLocationId, $date) {
            // Eager load locations with zones to avoid N+1 queries
            $fromLocation = Location::with('zone')->find($fromLocationId);
            $toLocation = Location::with('zone')->find($toLocationId);

            if (!$fromLocation || !$toLocation) {
                return collect();
            }

            $query = self::with(['vehicleType.serviceFeatures'])
                        ->where('service_type_id', $serviceTypeId)
                        ->valid($date);

            // First, try to find location-specific rates (these are overrides)
            $locationSpecificRates = (clone $query)
                ->where('from_location_id', $fromLocationId)
                ->where('to_location_id', $toLocationId)
                ->get();

            if ($locationSpecificRates->isNotEmpty()) {
                return $locationSpecificRates;
            }

            // Primary approach: zone-based rates
            return (clone $query)
                ->where('from_zone_id', $fromLocation->zone_id)
                ->where('to_zone_id', $toLocation->zone_id)
                ->get();
        });
    }

    /**
     * Find rates for zone-to-zone with caching
     */
    public static function findForZones($serviceTypeId, $fromZoneId, $toZoneId, $date = null)
    {
        $cacheKey = "zone_rates:{$serviceTypeId}:{$fromZoneId}:{$toZoneId}:" . ($date ? Carbon::parse($date)->format('Y-m-d') : 'today');
        
        return Cache::remember($cacheKey, self::ROUTE_CACHE_TTL, function () use ($serviceTypeId, $fromZoneId, $toZoneId, $date) {
            return self::with(['vehicleType.serviceFeatures'])
                        ->where('service_type_id', $serviceTypeId)
                        ->where('from_zone_id', $fromZoneId)
                        ->where('to_zone_id', $toZoneId)
                        ->valid($date)
                        ->get();
        });
    }

    /**
     * Get all rates for a service type with caching
     */
    public static function getByServiceType($serviceTypeId, $date = null)
    {
        $cacheKey = "service_rates:{$serviceTypeId}:" . ($date ? Carbon::parse($date)->format('Y-m-d') : 'today');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($serviceTypeId, $date) {
            return self::with(['vehicleType.serviceFeatures', 'fromZone', 'toZone', 'fromLocation', 'toLocation'])
                        ->where('service_type_id', $serviceTypeId)
                        ->valid($date)
                        ->get();
        });
    }

    /**
     * Clear cache when rates are updated
     */
    protected static function booted()
    {
        static::saved(function ($rate) {
            self::clearRateCache();
        });

        static::deleted(function ($rate) {
            self::clearRateCache();
        });
    }

    /**
     * Clear all rate-related cache
     */
    public static function clearRateCache(): void
    {
        $patterns = [
            'route_rates:*',
            'zone_rates:*',
            'service_rates:*',
        ];

        foreach ($patterns as $pattern) {
            $keys = Cache::get($pattern);
            if ($keys) {
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        }
    }

    /**
     * Check if this rate is zone-based
     */
    public function isZoneBased(): bool
    {
        return !is_null($this->from_zone_id) && !is_null($this->to_zone_id);
    }

    /**
     * Check if this rate is location-specific
     */
    public function isLocationSpecific(): bool
    {
        return !is_null($this->from_location_id) && !is_null($this->to_location_id);
    }

    /**
     * Get formatted price for display
     */
    public function getFormattedPrice(string $type = 'one_way'): string
    {
        $price = $type === 'round_trip' ? $this->total_round_trip : $this->total_one_way;
        return number_format($price, 2, '.', '');
    }

    /**
     * Check if rate is valid for given date
     */
    public function isValidForDate($date = null): bool
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        
        if (!$this->available) {
            return false;
        }

        if ($this->valid_from && $date < $this->valid_from) {
            return false;
        }

        if ($this->valid_to && $date > $this->valid_to) {
            return false;
        }

        return true;
    }
}
