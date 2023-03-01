<?php

namespace Database\Factories;

use App\Models\Username;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UsernameFactory extends Factory
{
    protected $model = Username::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->name(),
        ];
    }

    public function withId($id)
    {
        return $this->state([
            'user_id' => $id,
        ]);
    }
}

