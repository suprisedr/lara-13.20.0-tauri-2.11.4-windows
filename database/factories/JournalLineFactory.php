<?php

namespace Database\Factories;

use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalLine>
 */
class JournalLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id'      => \App\Models\Transaction::factory(),
            'chart_of_account_id' => \App\Models\ChartOfAccount::factory(),
            'type'                => fake()->randomElement(['debit', 'credit']),
            'amount'              => fake()->randomFloat(2, 10, 10000),
            'description'         => null,
        ];
    }
}
