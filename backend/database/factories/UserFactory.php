<?php

namespace Database\Factories;

use App\Enums\EtatCompte;
use App\Enums\RoleUtilisateur;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'           => fake()->lastName(),
            'prenom'         => fake()->firstName(),
            'email'          => fake()->unique()->safeEmail(),
            'password'       => 'Password123',
            'role'           => RoleUtilisateur::Candidat,
            'etat_compte'    => EtatCompte::Actif,
            'remember_token' => Str::random(10),
        ];
    }

    public function recruteur(): static
    {
        return $this->state(fn() => ['role' => RoleUtilisateur::Recruteur]);
    }

    public function administrateur(): static
    {
        return $this->state(fn() => ['role' => RoleUtilisateur::Administrateur]);
    }

    public function suspendu(): static
    {
        return $this->state(fn() => ['etat_compte' => EtatCompte::Suspendu]);
    }
}
