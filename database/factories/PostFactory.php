<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->realTextBetween(30, 60), '.!?');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->optional()->realTextBetween(100, 160),
            'body' => implode("\n\n", [
                fake()->realText(300),
                fake()->realText(300),
                fake()->realText(300),
            ]),
            'is_published' => false,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-1 year'),
        ]);
    }
}
