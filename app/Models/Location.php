<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
        'zone_id',
        'type',
        'active',
        'description',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'H' => 'Hotel',
            'B' => 'Bus Station',
            'F' => 'Ferry',
            'R' => 'Restaurant',
            'A' => 'Airport',
            default => 'Other'
        };
    }
}
