<?php

namespace Tests\Feature;

use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\Entretien;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_tableau_de_bord_candidat_expose_des_donnees_reelles(): void
    {
        $entreprise = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();
        $userRecruteur = User::factory()->recruteur()->create();
        $recruteur = Recruteur::factory()->create([
            'id_user' => $userRecruteur->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
        $offre = OffreEmploi::factory()->publieePar($recruteur, $departement)->create();
        $user = User::factory()->create();
        $candidat = Candidat::factory()->create(['id_user' => $user->id]);
        $candidature = Candidature::factory()->pour($candidat, $offre)->create();
        Entretien::factory()->pour($candidature)->create();

        $this->actingAs($user->refresh(), 'sanctum')
            ->getJson('/api/candidat/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('statistiques.candidatures', 1)
            ->assertJsonPath('statistiques.entretiens_a_venir', 1)
            ->assertJsonCount(1, 'candidatures_recentes');
    }

    public function test_le_tableau_de_bord_recruteur_est_limite_a_ses_offres(): void
    {
        $entreprise = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();
        $user = User::factory()->recruteur()->create();
        $recruteur = Recruteur::factory()->create([
            'id_user' => $user->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
        $offre = OffreEmploi::factory()->publieePar($recruteur, $departement)->create();
        $candidatUser = User::factory()->create();
        $candidat = Candidat::factory()->create(['id_user' => $candidatUser->id]);
        Candidature::factory()->pour($candidat, $offre)->create(['statut' => 'en_cours']);

        OffreEmploi::factory()->create();

        $this->actingAs($user->refresh(), 'sanctum')
            ->getJson('/api/recruteur/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('statistiques.offres', 1)
            ->assertJsonPath('statistiques.candidatures', 1)
            ->assertJsonPath('statistiques.en_attente', 0)
            ->assertJsonPath('statistiques.en_cours', 1)
            ->assertJsonCount(1, 'offres_recentes');
    }
}
