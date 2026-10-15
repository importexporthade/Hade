<?php

namespace Database\Factories;

use App\Enums\ComplaintItemType;
use App\Enums\ComplaintType;
use App\Enums\IdentityDocument;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => (int) now()->year,
            'sequence' => fake()->unique()->numberBetween(1, 999999),
            'type' => ComplaintType::Claim,
            'consumer_name' => fake()->name(),
            'document_type' => IdentityDocument::Dni,
            'document_number' => fake()->numerify('########'),
            'address' => fake()->streetAddress(),
            'phone' => fake()->numerify('9########'),
            'email' => fake()->safeEmail(),
            'is_minor' => false,
            'item_type' => ComplaintItemType::Product,
            'amount' => 76.90,
            'item_description' => 'Kit Hade Recargada',
            'detail' => fake()->paragraph(),
            'consumer_request' => fake()->sentence(),
        ];
    }

    public function answered(): static
    {
        return $this->state(fn (array $attributes) => [
            'response' => 'Te enviamos un kit nuevo sin costo.',
            'responded_at' => now(),
        ]);
    }
}
