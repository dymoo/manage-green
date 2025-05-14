<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant; // Import Tenant if needed for association
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(), // Default to creating a new tenant, can be overridden in tests.
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 5, 100), // Use 'price' column
            'sku' => fake()->unique()->ean8(),
            'weight' => fake()->optional()->randomFloat(3, 0.5, 100.0), // Optional weight
            // Corrected stock fields to match migration
            'current_stock' => fake()->randomFloat(3, 0, 1000.0), 
            'minimum_stock' => fake()->randomFloat(3, 5.0, 50.0),
            'active' => true, // Migration uses 'active'
            'attributes' => null, // Default attributes
            // 'category_id' => null, // Set category if needed
            // Removed price_per_unit and unit_of_measure if not in schema
            // 'unit_of_measure' => 'grams', // REMOVED - Not in migration
        ];
    }
}
