<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class City extends Model
{
    protected $fillable = [
        'name',
        'state',
        'country',
        'slug',
        'active',
        'description',
        'image'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    protected static function booted()
    {
        static::creating(function ($city) {
            if (empty($city->slug)) {
                $city->slug = Str::slug($city->name);
            }
        });
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function locations(): HasManyThrough
    {
        return $this->hasManyThrough(Location::class, Zone::class);
    }

    public function activeZones(): HasMany
    {
        return $this->zones()->where('active', true);
    }

    public function activeLocations(): HasManyThrough
    {
        return $this->hasManyThrough(Location::class, Zone::class)
                    ->where('locations.active', true);
    }

    public function airports(): HasMany
    {
        return $this->hasMany(Airport::class);
    }
}
