<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceFeature extends Model
{
    protected $fillable = [
        'name_en',
        'name_es',
        'description_en',
        'description_es',
        'icon',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function vehicleTypes(): BelongsToMany
    {
        return $this->belongsToMany(VehicleType::class, 'vehicle_type_service_feature');
    }

    // Scope para features activas ordenadas
    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }

    // Helper para obtener nombre en idioma específico
    public function getName($locale = 'en')
    {
        return $locale === 'es' ? $this->name_es : $this->name_en;
    }

    // Helper para obtener descripción en idioma específico
    public function getDescription($locale = 'en')
    {
        return $locale === 'es' ? $this->description_es : $this->description_en;
    }
}
