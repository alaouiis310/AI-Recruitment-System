<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntrepriseTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_un_utilisateur_authentifie_peut_lister_les_entreprises(): void
    {
        Entreprise::factory()->count(3)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/entreprises')
            ->assertOk()
            ->assertJsonStructure([
                'entreprises' => [['id_entreprise', 'nom', 'secteur', 'ville', 'nombre_departements', 'nombre_recruteurs']],
                'pagination'  => ['page_courante', 'par_page', 'total', 'derniere_page'],
            ])
            ->assertJsonCount(3, 'entreprises');
    }

    public function test_la_liste_des_entreprises_est_paginee_par_quinze(): void
    {
        Entreprise::factory()->count(18)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/entreprises')
            ->assertOk()
            ->assertJsonCount(15, 'entreprises')
            ->assertJsonPath('pagination.total', 18)
            ->assertJsonPath('pagination.par_page', 15)
            ->assertJsonPath('pagination.derniere_page', 2);
    }

    public function test_la_liste_des_entreprises_peut_etre_filtree_par_nom(): void
    {
        Entreprise::factory()->create(['nom' => 'Techno Maroc']);
        Entreprise::factory()->create(['nom' => 'Atlas Digital']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/entreprises?recherche=Techno')
            ->assertOk()
            ->assertJsonCount(1, 'entreprises')
            ->assertJsonPath('entreprises.0.nom', 'Techno Maroc');
    }

    public function test_la_liste_des_entreprises_peut_etre_filtree_par_ville(): void
    {
        Entreprise::factory()->create(['nom' => 'Alpha', 'ville' => 'Tanger']);
        Entreprise::factory()->create(['nom' => 'Beta', 'ville' => 'Casablanca']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/entreprises?ville=Tanger')
            ->assertOk()
            ->assertJsonCount(1, 'entreprises')
            ->assertJsonPath('entreprises.0.nom', 'Alpha');
    }

    public function test_un_per_page_hors_bornes_est_rejete(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/entreprises?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_la_liste_des_entreprises_exige_une_authentification(): void
    {
        $this->getJson('/api/entreprises')->assertUnauthorized();
    }

    public function test_un_utilisateur_authentifie_peut_consulter_une_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create(['nom' => 'TechnoMaroc']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/entreprises/{$entreprise->id_entreprise}")
            ->assertOk()
            ->assertJsonPath('entreprise.nom', 'TechnoMaroc');
    }

    public function test_une_entreprise_inexistante_retourne_404(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/entreprises/9999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Ressource introuvable.');
    }

    public function test_un_administrateur_peut_creer_une_entreprise(): void
    {
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/entreprises', [
                'nom'      => 'Atlas Digital',
                'secteur'  => 'Conseil et transformation digitale',
                'ville'    => 'Casablanca',
                'site_web' => 'https://atlasdigital.example.ma',
            ])
            ->assertCreated()
            ->assertJsonPath('entreprise.nom', 'Atlas Digital');

        $this->assertDatabaseHas('entreprises', ['nom' => 'Atlas Digital']);
    }

    public function test_la_creation_d_une_entreprise_exige_un_nom(): void
    {
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/entreprises', ['ville' => 'Rabat'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nom');
    }

    public function test_un_recruteur_ne_peut_pas_creer_une_entreprise(): void
    {
        $recruteur = $this->recruteurDe(Entreprise::factory()->create());

        $this->actingAs($recruteur, 'sanctum')
            ->postJson('/api/entreprises', ['nom' => 'Entreprise interdite'])
            ->assertForbidden();

        $this->assertDatabaseMissing('entreprises', ['nom' => 'Entreprise interdite']);
    }

    public function test_un_candidat_ne_peut_pas_creer_une_entreprise(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/entreprises', ['nom' => 'Entreprise interdite'])
            ->assertForbidden();
    }

    public function test_un_recruteur_peut_modifier_son_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create(['ville' => 'Tanger']);

        $this->actingAs($this->recruteurDe($entreprise), 'sanctum')
            ->patchJson("/api/entreprises/{$entreprise->id_entreprise}", ['ville' => 'Tetouan'])
            ->assertOk()
            ->assertJsonPath('entreprise.ville', 'Tetouan');

        $this->assertDatabaseHas('entreprises', [
            'id_entreprise' => $entreprise->id_entreprise,
            'ville'         => 'Tetouan',
        ]);
    }

    public function test_un_recruteur_ne_peut_pas_modifier_une_autre_entreprise(): void
    {
        $sienne = Entreprise::factory()->create();
        $autre  = Entreprise::factory()->create(['ville' => 'Casablanca']);

        $this->actingAs($this->recruteurDe($sienne), 'sanctum')
            ->patchJson("/api/entreprises/{$autre->id_entreprise}", ['ville' => 'Rabat'])
            ->assertForbidden();

        $this->assertDatabaseHas('entreprises', [
            'id_entreprise' => $autre->id_entreprise,
            'ville'         => 'Casablanca',
        ]);
    }

    public function test_un_administrateur_peut_modifier_n_importe_quelle_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->patchJson("/api/entreprises/{$entreprise->id_entreprise}", ['ville' => 'Agadir'])
            ->assertOk()
            ->assertJsonPath('entreprise.ville', 'Agadir');
    }

    public function test_la_modification_refuse_un_site_web_invalide(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->patchJson("/api/entreprises/{$entreprise->id_entreprise}", ['site_web' => 'pas-une-url'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('site_web');
    }

    public function test_la_modification_d_une_entreprise_exige_une_authentification(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->patchJson("/api/entreprises/{$entreprise->id_entreprise}", ['ville' => 'Oujda'])
            ->assertUnauthorized();
    }

    public function test_un_administrateur_peut_supprimer_une_entreprise_sans_recruteur(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->deleteJson("/api/entreprises/{$entreprise->id_entreprise}")
            ->assertNoContent();

        $this->assertDatabaseMissing('entreprises', ['id_entreprise' => $entreprise->id_entreprise]);
    }

    public function test_la_suppression_d_une_entreprise_employant_des_recruteurs_est_refusee(): void
    {
        $entreprise = Entreprise::factory()->create();
        $this->recruteurDe($entreprise);

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->deleteJson("/api/entreprises/{$entreprise->id_entreprise}")
            ->assertStatus(409)
            ->assertJsonStructure(['message', 'errors']);

        $this->assertDatabaseHas('entreprises', ['id_entreprise' => $entreprise->id_entreprise]);
    }

    public function test_un_recruteur_ne_peut_pas_supprimer_son_entreprise(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs($this->recruteurDe($entreprise), 'sanctum')
            ->deleteJson("/api/entreprises/{$entreprise->id_entreprise}")
            ->assertForbidden();

        $this->assertDatabaseHas('entreprises', ['id_entreprise' => $entreprise->id_entreprise]);
    }
}
