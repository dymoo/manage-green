<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(), // Member
            'staff_id' => User::factory(), // Staff
            'total_amount' => $this->faker->numberBetween(1000, 10000), // In cents
            'notes' => $this->faker->optional()->sentence,
            'created_at' => $this->faker->dateTimeThisMonth(),
            'updated_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
} 