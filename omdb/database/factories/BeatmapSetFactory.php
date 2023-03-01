<?php

namespace Database\Factories;

use App\Models\BeatmapSet;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeatmapSetFactory extends Factory
{
    protected $model = BeatmapSet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->randomNumber(6, true),
            'creator_id' => fake()->randomNumber(6, true),
            'date_ranked' => fake()->dateTime(),
            'artist' => fake()->name(),
            'title' => fake()->sentence(),
            'genre' => fake()->randomNumber(2),
            'language' => fake()->randomNumber(2),
        ];
    }
}
