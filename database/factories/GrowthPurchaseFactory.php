<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\GrowthPurchase>
 */
class GrowthPurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchased_on' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'amount' => fake()->numberBetween(1, 50) * 10_000,
            'fund_name' => 'eMAXIS Slim 米国株式(S&P500)',
            'note' => null,
        ];
    }
}
