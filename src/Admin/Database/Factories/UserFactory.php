<?php

namespace Igniter\Admin\Database\Factories;

use DateTimeInterface;
use Igniter\Flame\Database\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = \Igniter\Admin\Models\User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'username' => str_slug($this->faker->userName()),
            'date_activated' => $this->faker->dateTime()->format(DateTimeInterface::ATOM),
            'is_activated' => $this->faker->boolean(),
            'super_user' => $this->faker->boolean(),
            'status' => $this->faker->boolean(),
        ];
    }
}
