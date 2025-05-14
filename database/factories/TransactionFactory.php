<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, -100, 100); // Can be positive or negative
        $balance_before = fake()->randomFloat(2, 0, 500);
        $balance_after = $balance_before + $amount;
        $type = $amount > 0 ? 'top_up' : 'purchase'; // Basic logic

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'staff_id' => null, // Staff ID can be null or set explicitly
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'description' => fake()->sentence(4),
            'reference_id' => null, // Set if linked to Sale, TopUp, etc.
        ];
    }

    /**
     * Indicate a purchase transaction.
     */
    public function purchase(float $amount = null, float $balance_before = null): static
    {
        $amount = -abs($amount ?? fake()->randomFloat(2, 1, 100));
        $balance_before = $balance_before ?? fake()->randomFloat(2, abs($amount), 500);
        $balance_after = $balance_before + $amount;

        return $this->state(fn (array $attributes) => [
            'type' => 'purchase',
            'amount' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
        ]);
    }

    /**
     * Indicate a top-up transaction.
     */
    public function topUp(float $amount = null, float $balance_before = null): static
    {
        $amount = abs($amount ?? fake()->randomFloat(2, 5, 200));
        $balance_before = $balance_before ?? fake()->randomFloat(2, 0, 500);
        $balance_after = $balance_before + $amount;

        return $this->state(fn (array $attributes) => [
            'type' => 'top_up',
            'amount' => $amount,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
        ]);
    }
} 