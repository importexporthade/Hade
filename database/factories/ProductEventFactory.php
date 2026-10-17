<?php

namespace Database\Factories;

use App\Enums\ProductEventType;
use App\Models\Product;
use App\Models\ProductEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductEvent>
 */
class ProductEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => ProductEventType::View,
            'quantity' => 1,
            'visitor_id' => fake()->uuid(),
        ];
    }

    public function ofType(ProductEventType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
