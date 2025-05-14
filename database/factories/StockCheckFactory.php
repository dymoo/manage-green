<?php

namespace Database\Factories;

use App\Models\StockCheck;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\StockCheckType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockCheck>
 */
class StockCheckFactory extends Factory
{
    protected $model = StockCheck::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'staff_id' => User::factory(), // Staff who performed the check-in
            'type' => $this->faker->randomElement(StockCheckType::cases())->value, // Use new 'type' field
            'check_out_at' => null,
            'checked_out_by' => null,
            'start_notes' => $this->faker->sentence, // Use new 'start_notes'
            'end_notes' => null,
            'total_expected_value' => null, // Or some default, depends on usage
            'total_actual_value' => null,
            'total_discrepancy_value' => null,
        ];
    }

    /**
     * Indicate that the stock check is a check-in.
     */
    public function checkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockCheckType::CHECK_IN,
            'check_out_at' => null,
            'checked_out_by' => null,
        ]);
    }

    /**
     * Indicate that the stock check is a check-out.
     */
    public function checkOut(User $checkedOutBy = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockCheckType::CHECK_OUT, // Or keep original type if check-out modifies existing?
            // Ensure check_out_at is after check_in_at if check_in_at is explicitly set in attributes
            'check_out_at' => fake()->dateTimeBetween($attributes['check_in_at'] ?? '-' . mt_rand(1,60) . ' minutes' , 'now'), 
            'checked_out_by' => $checkedOutBy ? $checkedOutBy->id : User::factory(),
        ]);
    }
} 