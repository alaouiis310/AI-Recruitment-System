<?php

namespace Tests\Feature;

use App\Enums\EtatCompte;
use App\Enums\StatutCandidature;
use App\Enums\TypeContrat;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Recruteur $recruteur;

    private Departement $departement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrateur()->create();
        $entreprise = Entreprise::factory()->create();
        $this->departement = Departement::factory()->pour($entreprise)->create();
        $user = User::factory()->recruteur()->create();
        $this->recruteur = Recruteur::factory()->create([
            'id_user' => $user->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
    }

    public function test_un_administrateur_consulte_son_tableau_de_bord(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('statistiques.utilisateurs.recruteurs', 1)
            ->assertJsonStructure(['statistiques' => ['offres', 'candidatures', 'traitements', 'evolution_candidatures']]);
    }

    public function test_un_candidat_ne_peut_pas_acceder_a_l_administration(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/admin/candidats')
            ->assertForbidden();
    }

    public function test_l_administrateur_liste_et_recherche_les_candidats(): void
    {
        $trouve = User::factory()->create(['name' => 'ChercheMoi']);
        Candidat::factory()->create(['id_user' => $trouve->id]);
        $autre = User::factory()->create(['name' => 'Autre']);
        Candidat::factory()->create(['id_user' => $autre->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/candidats?recherche=ChercheMoi')
            ->assertOk()
            ->assertJsonCount(1, 'candidats')
            ->assertJsonPath('candidats.0.utilisateur.nom', 'ChercheMoi');
    }

    public function test_l_administrateur_cree_un_candidat(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/candidats', [
                'nom' => 'Nouveau',
                'prenom' => 'Candidat',
                'email' => 'nouveau.candidat@example.ma',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
                'telephone' => '0600000001',
            ])
            ->assertCreated()
            ->assertJsonPath('utilisateur.role', 'candidat');

        $this->assertDatabaseHas('users', ['email' => 'nouveau.candidat@example.ma']);
        $this->assertDatabaseCount('candidats', 1);
    }

    public function test_l_administrateur_cree_un_recruteur(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/recruteurs', [
                'nom' => 'Nouveau',
                'prenom' => 'Recruteur',
                'email' => 'nouveau.recruteur@example.ma',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
                'id_entreprise' => $this->recruteur->id_entreprise,
            ])
            ->assertCreated()
            ->assertJsonPath('utilisateur.role', 'recruteur');
    }

    public function test_l_administrateur_suspend_un_compte_et_revoque_ses_jetons(): void
    {
        $user = User::factory()->create();
        $user->createToken('test');

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/utilisateurs/{$user->id}/etat", [
                'etat_compte' => EtatCompte::Suspendu->value,
            ])
            ->assertOk()
            ->assertJsonPath('utilisateur.etat_compte', EtatCompte::Suspendu->value);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_l_administrateur_ne_suspend_pas_son_propre_compte(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/utilisateurs/{$this->admin->id}/etat", [
                'etat_compte' => EtatCompte::Suspendu->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('etat_compte');
    }

    public function test_l_administrateur_liste_les_offres_de_tous_les_statuts(): void
    {
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->fermee()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/offres')
            ->assertOk()
            ->assertJsonCount(2, 'offres');
    }

    public function test_l_administrateur_cree_une_offre_pour_un_recruteur(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/offres', [
                'titre' => 'Ingénieur QA',
                'description' => 'Automatisation des tests applicatifs.',
                'type_contrat' => TypeContrat::Cdi->value,
                'localisation' => 'Tanger',
                'id_recruteur' => $this->recruteur->id_recruteur,
                'id_departement' => $this->departement->id_departement,
            ])
            ->assertCreated()
            ->assertJsonPath('offre.titre', 'Ingénieur QA');
    }

    public function test_une_offre_admin_respecte_l_entreprise_du_recruteur(): void
    {
        $autreDepartement = Departement::factory()->pour(Entreprise::factory()->create())->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/offres', [
                'titre' => 'Offre invalide',
                'description' => 'Le département ne correspond pas.',
                'type_contrat' => TypeContrat::Cdi->value,
                'localisation' => 'Rabat',
                'id_recruteur' => $this->recruteur->id_recruteur,
                'id_departement' => $autreDepartement->id_departement,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_departement');
    }

    public function test_l_administrateur_liste_et_exporte_les_candidatures(): void
    {
        $candidatUser = User::factory()->create();
        $candidat = Candidat::factory()->create(['id_user' => $candidatUser->id]);
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        Candidature::factory()->pour($candidat, $offre)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/candidatures')
            ->assertOk()
            ->assertJsonCount(1, 'candidatures');

        $this->actingAs($this->admin, 'sanctum')
            ->get('/api/admin/candidatures/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();
    }

    public function test_l_administrateur_fait_avancer_une_candidature(): void
    {
        $candidatUser = User::factory()->create();
        $candidat = Candidat::factory()->create(['id_user' => $candidatUser->id]);
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        $candidature = Candidature::factory()->pour($candidat, $offre)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/candidatures/{$candidature->id_candidature}/statut", [
                'statut' => StatutCandidature::EnCours->value,
            ])
            ->assertOk()
            ->assertJsonPath('candidature.statut', StatutCandidature::EnCours->value);
    }
}
