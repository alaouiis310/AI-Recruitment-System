<?php

namespace Tests\Feature;

use App\Enums\EtatCompte;
use App\Enums\StatutCandidature;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Compléments d'API ajoutés lors du raccordement du frontend. */
class CompletionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_candidat_peut_desactiver_son_compte(): void
    {
        $user = User::factory()->create(['password' => 'Password123']);
        $user->createToken('session');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/desactivation', ['password' => 'Password123'])
            ->assertOk();

        $this->assertSame(EtatCompte::Desactive, $user->fresh()->etat_compte);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_la_desactivation_exige_le_bon_mot_de_passe(): void
    {
        $user = User::factory()->create(['password' => 'Password123']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/desactivation', ['password' => 'MauvaisMotDePasse1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertSame(EtatCompte::Actif, $user->fresh()->etat_compte);
    }

    public function test_un_administrateur_ne_peut_pas_desactiver_son_propre_compte(): void
    {
        $admin = User::factory()->administrateur()->create(['password' => 'Password123']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/auth/desactivation', ['password' => 'Password123'])
            ->assertForbidden();
    }

    /** Recruteur, son offre et trois candidatures aux statuts variés. */
    private function jeuDeDonnees(): array
    {
        $entreprise = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();
        $userRecruteur = User::factory()->recruteur()->create();
        $recruteur = Recruteur::factory()->create([
            'id_user' => $userRecruteur->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
        $offre = OffreEmploi::factory()->publieePar($recruteur, $departement)->create();

        $alami = Candidat::factory()->create(['id_user' => User::factory()->create(['name' => 'Alami', 'prenom' => 'Youssef'])->id]);
        $idrissi = Candidat::factory()->create(['id_user' => User::factory()->create(['name' => 'Idrissi', 'prenom' => 'Karim'])->id]);
        $autre = Candidat::factory()->create();

        Candidature::factory()->pour($alami, $offre)->create();
        Candidature::factory()->pour($idrissi, $offre)->statut(StatutCandidature::Acceptee)->create([
            'date_candidature' => now()->subDays(10)->toDateString(),
            'date_decision' => now()->subDays(4)->toDateString(),
        ]);
        Candidature::factory()->pour($autre, $offre)->statut(StatutCandidature::Refusee)->create([
            'date_candidature' => now()->subDays(8)->toDateString(),
            'date_decision' => now()->subDays(6)->toDateString(),
        ]);

        return [$userRecruteur->refresh(), $alami];
    }

    public function test_le_tableau_de_bord_recruteur_compte_les_candidatures_en_attente_et_acceptees(): void
    {
        [$recruteur] = $this->jeuDeDonnees();

        $this->actingAs($recruteur, 'sanctum')
            ->getJson('/api/recruteur/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('statistiques.en_attente', 1)
            ->assertJsonPath('statistiques.acceptees', 1);
    }

    public function test_le_tableau_de_bord_candidat_compte_les_refus(): void
    {
        $this->jeuDeDonnees();
        $candidat = Candidat::whereHas('candidatures', fn ($q) => $q->where('statut', 'refusee'))->first();

        $this->actingAs($candidat->user->refresh(), 'sanctum')
            ->getJson('/api/candidat/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('statistiques.refusees', 1);
    }

    public function test_le_recruteur_recherche_ses_candidatures_par_nom_de_candidat(): void
    {
        [$recruteur] = $this->jeuDeDonnees();

        $this->actingAs($recruteur, 'sanctum')
            ->getJson('/api/recruteur/candidatures?recherche=Alami')
            ->assertOk()
            ->assertJsonCount(1, 'candidatures')
            ->assertJsonPath('candidatures.0.candidat.utilisateur.nom', 'Alami');
    }

    public function test_les_analytiques_donnent_le_delai_moyen_de_decision(): void
    {
        $this->jeuDeDonnees();

        // Décisions en 6 et 2 jours : moyenne de 4.
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->getJson('/api/admin/analytiques')
            ->assertOk()
            ->assertJsonPath('statistiques.candidatures.delai_moyen_decision_jours', 4);
    }
}
