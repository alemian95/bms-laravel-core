<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'url' => $this->faker->url(),
            'title' => $this->faker->sentence(),
            'domain' => $this->faker->domainName(),
            'author' => $this->faker->name(),
            'thumbnail_url' => $this->faker->imageUrl(),
            'content_html' => '<p>'.$this->faker->paragraph().'</p>',
            'content_text' => $this->faker->paragraph(),
            'reading_progress' => $this->faker->numberBetween(0, 100),
            'scroll_position' => $this->faker->numberBetween(0, 5000),
            'status' => 'parsed',
        ];
    }

    /**
     * Indicate that the bookmark is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'title' => null,
            'domain' => null,
            'author' => null,
            'thumbnail_url' => null,
            'content_html' => null,
            'content_text' => null,
            'reading_progress' => 0,
            'scroll_position' => 0,
        ]);
    }

    /**
     * Indicate that the bookmark parsing failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
