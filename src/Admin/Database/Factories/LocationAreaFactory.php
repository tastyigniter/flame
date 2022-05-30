<?php

namespace Igniter\Admin\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class LocationAreaFactory extends Factory
{
    protected $model = \Igniter\Admin\Models\LocationArea::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
            'type' => $this->faker->randomElement(['address', 'circle', 'polygon']),
            'color' => $this->faker->hexColor(),
            'is_default' => $this->faker->boolean(),
        ];
    }
}
