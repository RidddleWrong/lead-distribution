<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ManagerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'is_active' => true,
        ];
    }
}
