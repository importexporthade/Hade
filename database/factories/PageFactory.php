<?php

namespace Database\Factories;

use App\Enums\PageVisibility;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => Str::ucfirst($title),
            'slug' => Str::slug($title),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'visibility' => PageVisibility::MenuAndFooter,
            'sort_order' => 0,
        ];
    }
}
