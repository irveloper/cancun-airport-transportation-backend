<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    protected $fillable = [
        'from_location_id',
        'to_location_id',
        'estimated_time',
        'distance_km',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'distance_km' => 'decimal:2',
    ];

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    // Scope para rutas activas
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
