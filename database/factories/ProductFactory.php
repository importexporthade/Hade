<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'price' => fake()->randomFloat(2, 15, 150),
            'compare_at_price' => null,
            'images' => [],
            'is_visible' => true,
            'is_featured' => false,
            'is_in_stock' => true,
            'ships_to_capital' => true,
            'ships_to_province' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * A product whose previous price is crossed out.
     */
    public function onSale(float $price = 76.90, float $compareAtPrice = 90.00): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
            'compare_at_price' => $compareAtPrice,
        ]);
    }

    public function markedAsNew(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
