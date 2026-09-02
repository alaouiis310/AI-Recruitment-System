<?php

namespace Tests\Feature;

use App\Enums\EtatCompte;
use App\Enums\RoleUtilisateur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_candidat_peut_s_inscrire(): void
    {
        $reponse = $this->postJson('/api/auth/inscription/candidat', [
            'nom'                   => 'Alami',
            'prenom'                => 'Youssef',
            'email'                 => 'youssef@example.ma',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $reponse->assertCreated()
            ->assertJsonStructure(['message', 'utilisateur', 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'youssef@example.ma',
            'role'  => 'candidat',
        ]);

        $this->assertNotSame('Password123', User::first()->password);
    }

    public function test_une_adresse_email_ne_peut_pas_etre_reutilisee(): void
    {
        User::factory()->create(['email' => 'doublon@example.ma']);

        $this->postJson('/api/auth/inscription/candidat', [
            'nom'                   => 'Test',
            'prenom'                => 'Test',
            'email'                 => 'doublon@example.ma',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_un_recruteur_s_inscrit_avec_une_entreprise(): void
    {
        $this->postJson('/api/auth/inscription/recruteur', [
            'nom'                   => 'Bennani',
            'prenom'                => 'Salma',
            'email'                 => 'salma@example.ma',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
            'entreprise'            => ['nom' => 'TechnoMaroc', 'ville' => 'Tanger'],
        ])->assertCreated();

        $this->assertDatabaseHas('entreprises', ['nom' => 'TechnoMaroc']);
    }

    public function test_connexion_avec_identifiants_valides(): void
    {
        User::factory()->create([
            'email'    => 'test@example.ma',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/auth/connexion', [
            'email'    => 'test@example.ma',
            'password' => 'Password123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_connexion_refusee_avec_mauvais_mot_de_passe(): void
    {
        User::factory()->create([
            'email'    => 'test@example.ma',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/auth/connexion', [
            'email'    => 'test@example.ma',
            'password' => 'MauvaisMotDePasse',
        ])->assertUnauthorized();
    }

    public function test_un_compte_suspendu_ne_peut_pas_se_connecter(): void
    {
        User::factory()->create([
            'email'       => 'suspendu@example.ma',
            'password'    => 'Password123',
            'etat_compte' => EtatCompte::Suspendu,
        ]);

        $this->postJson('/api/auth/connexion', [
            'email'    => 'suspendu@example.ma',
            'password' => 'Password123',
        ])->assertForbidden();
    }

    public function test_la_route_moi_exige_un_jeton(): void
    {
        $this->getJson('/api/auth/moi')->assertUnauthorized();
    }

    public function test_un_candidat_ne_peut_pas_acceder_a_l_espace_recruteur(): void
    {
        /** @var \App\Models\User $candidat */
        $candidat = User::factory()->create(['role' => RoleUtilisateur::Candidat]);

        $this->actingAs($candidat, 'sanctum')
            ->getJson('/api/recruteur/tableau-de-bord')
            ->assertForbidden();
    }

    public function test_la_deconnexion_revoque_le_jeton(): void
    {
        $user  = User::factory()->create(['password' => 'Password123']);
        $jeton = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$jeton}")
            ->postJson('/api/auth/deconnexion')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
