<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Rate extends Model
{
    protected $fillable = [
        'service_type_id',
        'vehicle_type_id',
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
}
