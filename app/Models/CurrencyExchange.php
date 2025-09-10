<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyExchange extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'exchange_rate'
    ];

    public static function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $exchange = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->first();

        if (!$exchange) {
            $reverseExchange = self::where('from_currency', $toCurrency)
                ->where('to_currency', $fromCurrency)
                ->first();
            
            if ($reverseExchange) {
                return 1 / $reverseExchange->exchange_rate;
            }
            
            return 1.0;
        }

        return $exchange->exchange_rate;
    }
}
