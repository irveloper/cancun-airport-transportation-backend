<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public static function findOrCreateFromBookingData(array $customerData): self
    {
        $customer = self::where('email', $customerData['email'])->first();
        
        if (!$customer) {
            $customer = self::create([
                'first_name' => $customerData['firstName'],
                'last_name' => $customerData['lastName'] ?? '',
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'country' => $customerData['country'],
            ]);
        } else {
            $customer->update([
                'first_name' => $customerData['firstName'],
                'last_name' => $customerData['lastName'] ?? '',
                'phone' => $customerData['phone'],
                'country' => $customerData['country'],
            ]);
        }

        return $customer;
    }
}
