<?php

namespace Database\Factories;

use App\Models\Beatmap;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeatmapFactory extends Factory
{
    protected $model = Beatmap::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->randomNumber(6, true),
            'difficulty_name' => fake()->word(),
            'mode' => fake()->randomNumber(1),
            'status' => fake()->randomNumber(1),
            'star_rating' => fake()->randomFloat(1),
        ];
    }

    public function withId($id)
    {
        return $this->state([
            'beatmapset_id' => $id,
        ]);
    }
}
