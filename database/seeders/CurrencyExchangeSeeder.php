<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CurrencyExchange;

class CurrencyExchangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exchanges = [
            [
                'from_currency' => 'USD',
                'to_currency' => 'MXN',
                'exchange_rate' => 20.00, // 1 USD = 20 MXN (approximate)
            ],
            [
                'from_currency' => 'MXN',
                'to_currency' => 'USD',
                'exchange_rate' => 0.05, // 1 MXN = 0.05 USD
            ],
        ];

        foreach ($exchanges as $exchange) {
            CurrencyExchange::updateOrCreate(
                [
                    'from_currency' => $exchange['from_currency'],
                    'to_currency' => $exchange['to_currency']
                ],
                ['exchange_rate' => $exchange['exchange_rate']]
            );
        }
    }
}
