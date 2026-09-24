<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartementTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_departement_portant_des_offres_ne_peut_pas_etre_supprime(): void
    {
        $entreprise = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();
        $utilisateur = $this->recruteurDe($entreprise);
        OffreEmploi::factory()->publieePar($utilisateur->recruteur, $departement)->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->deleteJson("/api/departements/{$departement->id_departement}")
            ->assertStatus(409);

        $this->assertDatabaseHas('departements', ['id_departement' => $departement->id_departement]);
    }

    /** Crée un recruteur rattaché à l'entreprise donnée. */
    private function recruteurDe(Entreprise $entreprise): User
    {
        $user = User::factory()->recruteur()->create();

        Recruteur::factory()->create([
            'id_user'       => $user->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);

        // Sans rafraîchissement, la relation recruteur peut rester en cache à null.
        return $user->refresh();
    }

    public function test_un_utilisateur_authentifie_peut_lister_les_departements_d_une_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        Departement::factory()->count(3)->pour($entreprise)->create();

        // Une autre entreprise possède ses propres départements : ils ne doivent pas apparaître dans la liste.
        Departement::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/entreprises/{$entreprise->id_entreprise}/departements")
            ->assertOk()
            ->assertJsonStructure([
                'departements' => [['id_departement', 'nom', 'description', 'id_entreprise']],
                'pagination'   => ['page_courante', 'par_page', 'total', 'derniere_page'],
            ])
            ->assertJsonCount(3, 'departements')
            ->assertJsonPath('pagination.total', 3);
    }

    public function test_la_liste_des_departements_est_paginee(): void
    {
        $entreprise = Entreprise::factory()->create();

        foreach (range(1, 18) as $i) {
            Departement::factory()->pour($entreprise)->create(['nom' => "Departement {$i}"]);
        }

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/entreprises/{$entreprise->id_entreprise}/departements?per_page=5")
            ->assertOk()
            ->assertJsonCount(5, 'departements')
            ->assertJsonPath('pagination.total', 18)
            ->assertJsonPath('pagination.derniere_page', 4);
    }

    public function test_la_consultation_des_departements_exige_une_authentification(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->getJson("/api/entreprises/{$entreprise->id_entreprise}/departements")
            ->assertUnauthorized();
    }

    public function test_un_departement_inexistant_retourne_404(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/departements/9999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Ressource introuvable.');
    }

    public function test_un_administrateur_peut_creer_un_departement(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson("/api/entreprises/{$entreprise->id_entreprise}/departements", [
                'nom'         => 'Ressources humaines',
                'description' => 'Gestion du personnel.',
            ])
            ->assertCreated()
            ->assertJsonPath('departement.nom', 'Ressources humaines');

        $this->assertDatabaseHas('departements', [
            'nom'           => 'Ressources humaines',
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
    }

    public function test_un_recruteur_peut_creer_un_departement_dans_son_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs($this->recruteurDe($entreprise), 'sanctum')
            ->postJson("/api/entreprises/{$entreprise->id_entreprise}/departements", ['nom' => 'Qualite'])
            ->assertCreated();

        $this->assertDatabaseHas('departements', [
            'nom'           => 'Qualite',
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
    }

    public function test_un_recruteur_ne_peut_pas_creer_un_departement_dans_une_autre_entreprise(): void
    {
        $sienne = Entreprise::factory()->create();
        $autre  = Entreprise::factory()->create();

        $this->actingAs($this->recruteurDe($sienne), 'sanctum')
            ->postJson("/api/entreprises/{$autre->id_entreprise}/departements", ['nom' => 'Intrusion'])
            ->assertForbidden();

        $this->assertDatabaseMissing('departements', ['nom' => 'Intrusion']);
    }

    public function test_un_candidat_ne_peut_pas_creer_un_departement(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/entreprises/{$entreprise->id_entreprise}/departements", ['nom' => 'Interdit'])
            ->assertForbidden();
    }

    public function test_le_nom_du_departement_est_obligatoire(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson("/api/entreprises/{$entreprise->id_entreprise}/departements", ['description' => 'Sans nom'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nom');
    }

    public function test_deux_departements_ne_peuvent_pas_porter_le_meme_nom_dans_une_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();
        Departement::factory()->pour($entreprise)->create(['nom' => 'Ressources humaines']);

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson("/api/entreprises/{$entreprise->id_entreprise}/departements", ['nom' => 'Ressources humaines'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nom');
    }

    public function test_deux_entreprises_peuvent_avoir_un_departement_homonyme(): void
    {
        $premiere = Entreprise::factory()->create();
        $seconde  = Entreprise::factory()->create();

        Departement::factory()->pour($premiere)->create(['nom' => 'Ressources humaines']);

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson("/api/entreprises/{$seconde->id_entreprise}/departements", ['nom' => 'Ressources humaines'])
            ->assertCreated();

        $this->assertDatabaseCount('departements', 2);
    }

    public function test_le_departement_est_rattache_a_l_entreprise_de_l_url(): void
    {
        $urlEntreprise    = Entreprise::factory()->create();
        $autreEntreprise  = Entreprise::factory()->create();

        // Un id_entreprise falsifie dans le corps doit rester sans effet (RG9).
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson("/api/entreprises/{$urlEntreprise->id_entreprise}/departements", [
                'nom'           => 'Comptabilite',
                'id_entreprise' => $autreEntreprise->id_entreprise,
            ])
            ->assertCreated()
            ->assertJsonPath('departement.id_entreprise', $urlEntreprise->id_entreprise);

        $this->assertDatabaseHas('departements', [
            'nom'           => 'Comptabilite',
            'id_entreprise' => $urlEntreprise->id_entreprise,
        ]);
        $this->assertDatabaseMissing('departements', [
            'nom'           => 'Comptabilite',
            'id_entreprise' => $autreEntreprise->id_entreprise,
        ]);
    }

    public function test_un_recruteur_peut_modifier_un_departement_de_son_entreprise(): void
    {
        $entreprise  = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create(['nom' => 'Commercial']);

        $this->actingAs($this->recruteurDe($entreprise), 'sanctum')
            ->patchJson("/api/departements/{$departement->id_departement}", ['nom' => 'Ventes'])
            ->assertOk()
            ->assertJsonPath('departement.nom', 'Ventes');

        $this->assertDatabaseHas('departements', [
            'id_departement' => $departement->id_departement,
            'nom'            => 'Ventes',
        ]);
    }

    public function test_un_departement_peut_conserver_son_nom_lors_d_une_modification(): void
    {
        $entreprise  = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create(['nom' => 'Commercial']);

        // La regle d'unicite doit ignorer l'enregistrement courant.
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->patchJson("/api/departements/{$departement->id_departement}", [
                'nom'         => 'Commercial',
                'description' => 'Equipe de vente.',
            ])
            ->assertOk()
            ->assertJsonPath('departement.description', 'Equipe de vente.');
    }

    public function test_un_recruteur_ne_peut_pas_modifier_le_departement_d_une_autre_entreprise(): void
    {
        $sienne      = Entreprise::factory()->create();
        $autre       = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($autre)->create(['nom' => 'Commercial']);

        $this->actingAs($this->recruteurDe($sienne), 'sanctum')
            ->patchJson("/api/departements/{$departement->id_departement}", ['nom' => 'Detourne'])
            ->assertForbidden();

        $this->assertDatabaseHas('departements', [
            'id_departement' => $departement->id_departement,
            'nom'            => 'Commercial',
        ]);
    }

    public function test_un_recruteur_peut_supprimer_un_departement_de_son_entreprise(): void
    {
        $entreprise  = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();

        $this->actingAs($this->recruteurDe($entreprise), 'sanctum')
            ->deleteJson("/api/departements/{$departement->id_departement}")
            ->assertNoContent();

        $this->assertDatabaseMissing('departements', ['id_departement' => $departement->id_departement]);
    }

    public function test_un_recruteur_ne_peut_pas_supprimer_le_departement_d_une_autre_entreprise(): void
    {
        $sienne      = Entreprise::factory()->create();
        $autre       = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($autre)->create();

        $this->actingAs($this->recruteurDe($sienne), 'sanctum')
            ->deleteJson("/api/departements/{$departement->id_departement}")
            ->assertForbidden();

        $this->assertDatabaseHas('departements', ['id_departement' => $departement->id_departement]);
    }
}
