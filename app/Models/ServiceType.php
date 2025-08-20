<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'tpv_type',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
