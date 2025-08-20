<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VehicleType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'image',
        'max_units',
        'max_pax',
        'travel_time',
        'video_url',
        'frame',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function serviceFeatures(): BelongsToMany
    {
        return $this->belongsToMany(ServiceFeature::class, 'vehicle_type_service_feature')->orderBy('sort_order');
    }

    // Helper para obtener features activas
    public function getActiveFeatures()
    {
        return $this->serviceFeatures()->active()->get();
    }
}
