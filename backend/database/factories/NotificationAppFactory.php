<?php

namespace Database\Factories;

use App\Models\NotificationApp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationApp> */
class NotificationAppFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user'    => User::factory(),
            'message'    => fake()->sentence(10),
            'date_envoi' => now(),
            'lu'         => false,
        ];
    }

    public function pour(User $user): static
    {
        return $this->state(fn () => ['id_user' => $user->id]);
    }

    public function lue(): static
    {
        return $this->state(fn () => ['lu' => true]);
    }
}
