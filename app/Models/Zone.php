<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = [
        'name',
        'city_id',
        'active',
        'description'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function activeLocations(): HasMany
    {
        return $this->locations()->where('active', true);
    }
}
