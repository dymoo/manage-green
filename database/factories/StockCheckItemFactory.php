<?php

namespace Database\Factories;

use App\Models\StockCheck;
use App\Models\Product;
use App\Models\StockCheckItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockCheckItem>
 */
class StockCheckItemFactory extends Factory
{
    protected $model = StockCheckItem::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startQuantity = fake()->randomFloat(3, 10, 200);
        $endQuantity = fake()->optional(0.7)->randomFloat(3, 0, $startQuantity); // Null 30% of the time
        $discrepancy = null;
        if ($endQuantity !== null) {
            // Simple discrepancy calculation (assumes quantity_sold is 0 for now)
            $discrepancy = $startQuantity - $endQuantity;
        }

        return [
            'stock_check_id' => StockCheck::factory(),
            'product_id' => Product::factory(),
            'start_quantity' => $startQuantity,
            'end_quantity' => $endQuantity,
            'quantity_sold' => null, // Assuming not tracked here by default
            'discrepancy' => $discrepancy,
        ];
    }
}
