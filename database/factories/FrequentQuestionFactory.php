<?php

namespace Database\Factories;

use App\Models\FrequentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FrequentQuestion>
 */
class FrequentQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => '¿'.rtrim(fake()->sentence(), '.').'?',
            'answer' => '<p>'.fake()->paragraph().'</p>',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
