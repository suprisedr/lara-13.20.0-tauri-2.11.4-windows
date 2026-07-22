<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id'       => \App\Models\Company::factory(),
            'user_id'          => \App\Models\User::factory(),
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'description'      => fake()->sentence(4),
            'reference'        => strtoupper(fake()->bothify('JNL-####')),
            'status'           => 'posted',
            'notes'            => null,
        ];
    }
}
