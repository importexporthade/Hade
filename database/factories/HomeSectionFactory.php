<?php

namespace Database\Factories;

use App\Enums\HomeSectionType;
use App\Models\Category;
use App\Models\HomeSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeSection>
 */
class HomeSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'type' => HomeSectionType::Latest,
            'products_limit' => 5,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function ofType(HomeSectionType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HomeSectionType::Category,
            'category_id' => $category->id,
        ]);
    }
}
