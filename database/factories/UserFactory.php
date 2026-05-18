<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'code' => fake()->unique()->bothify('USER####'),
            'password' => Hash::make('password'),
            'name' => fake()->name(),
            'role' => 'user',
            'delflag' => false,
            'remember_token' => Str::random(10),
        ];
    }
}
