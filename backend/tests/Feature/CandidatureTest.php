<?php

namespace Tests\Feature;

use App\Enums\StatutCandidature;
use App\Enums\StatutOffre;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidatureTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateurCandidat;

    private Candidat $candidat;

    private User $utilisateurRecruteur;

    private Recruteur $recruteur;

    private OffreEmploi $offre;

    protected function setUp(): void
    {
        parent::setUp();

        $entreprise  = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();

        $this->utilisateurRecruteur = User::factory()->recruteur()->create();
        $this->recruteur = Recruteur::factory()->create([
            'id_user'       => $this->utilisateurRecruteur->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
        $this->utilisateurRecruteur->refresh();

        $this->utilisateurCandidat = User::factory()->create();
        $this->candidat = Candidat::factory()->create(['id_user' => $this->utilisateurCandidat->id]);
        $this->utilisateurCandidat->refresh();

        $this->offre = OffreEmploi::factory()
            ->publieePar($this->recruteur, $departement)
            ->create();
    }

    /** Recruteur d'une autre entreprise, avec sa propre offre. */
    private function autreRecruteur(): array
    {
        $entreprise  = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();

        $user = User::factory()->recruteur()->create();
        $recruteur = Recruteur::factory()->create([
            'id_user'       => $user->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);

        $offre = OffreEmploi::factory()->publieePar($recruteur, $departement)->create();

        return [$user->refresh(), $recruteur, $offre];
    }

    // -------------------------------------------------------------------
    // Depot — RG27, RG31, RG33
    // -------------------------------------------------------------------

    public function test_un_candidat_peut_postuler_a_une_offre(): void
    {
        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->postJson('/api/candidat/candidatures', [
                'id_offre'          => $this->offre->id_offre,
                'lettre_motivation' => 'Je suis vivement interesse par ce poste.',
            ])
            ->assertCreated()
            ->assertJsonPath('candidature.statut', StatutCandidature::EnAttente->value)
            ->assertJsonPath('candidature.date_candidature', now()->toDateString());

        $this->assertDatabaseHas('candidatures', [
            'id_candidat' => $this->candidat->id_candidat,
            'id_offre'    => $this->offre->id_offre,
        ]);
    }

    public function test_un_candidat_ne_peut_pas_postuler_deux_fois_a_la_meme_offre(): void
    {
        Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->postJson('/api/candidat/candidatures', ['id_offre' => $this->offre->id_offre])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Vous avez déjà postulé à cette offre.')
            ->assertJsonValidationErrors('id_offre');

        $this->assertDatabaseCount('candidatures', 1);
    }

    public function test_on_ne_peut_pas_postuler_a_une_offre_fermee(): void
    {
        $this->offre->update(['statut' => StatutOffre::Fermee]);

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->postJson('/api/candidat/candidatures', ['id_offre' => $this->offre->id_offre])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_offre');

        $this->assertDatabaseCount('candidatures', 0);
    }

    public function test_on_ne_peut_pas_postuler_a_une_offre_expiree(): void
    {
        $this->offre->update(['date_expiration' => now()->subDay()->toDateString()]);

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->postJson('/api/candidat/candidatures', ['id_offre' => $this->offre->id_offre])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_offre');
    }

    public function test_le_depot_exige_une_offre_existante(): void
    {
        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->postJson('/api/candidat/candidatures', ['id_offre' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_offre');
    }

    public function test_un_recruteur_ne_peut_pas_postuler(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/candidat/candidatures', ['id_offre' => $this->offre->id_offre])
            ->assertForbidden();
    }

    public function test_le_depot_exige_une_authentification(): void
    {
        $this->postJson('/api/candidat/candidatures', ['id_offre' => $this->offre->id_offre])
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------
    // Liste du candidat — RG27
    // -------------------------------------------------------------------

    public function test_un_candidat_ne_voit_que_ses_propres_candidatures(): void
    {
        Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $autreCandidat = Candidat::factory()->create();
        Candidature::factory()->pour($autreCandidat, $this->offre)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson('/api/candidat/candidatures')
            ->assertOk()
            ->assertJsonCount(1, 'candidatures')
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_la_liste_du_candidat_peut_etre_filtree_par_statut(): void
    {
        Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $autreOffre = OffreEmploi::factory()->publieePar($this->recruteur, $this->offre->departement)->create();
        Candidature::factory()->pour($this->candidat, $autreOffre)
            ->statut(StatutCandidature::Refusee)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson('/api/candidat/candidatures?statut='.StatutCandidature::Refusee->value)
            ->assertOk()
            ->assertJsonCount(1, 'candidatures')
            ->assertJsonPath('candidatures.0.statut', StatutCandidature::Refusee->value);
    }

    public function test_un_candidat_peut_retirer_sa_candidature(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->deleteJson("/api/candidat/candidatures/{$candidature->id_candidature}")
            ->assertNoContent();

        $this->assertDatabaseCount('candidatures', 0);
    }

    public function test_un_candidat_ne_peut_pas_retirer_une_candidature_deja_tranchee(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)
            ->statut(StatutCandidature::Acceptee)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->deleteJson("/api/candidat/candidatures/{$candidature->id_candidature}")
            ->assertForbidden();

        $this->assertDatabaseCount('candidatures', 1);
    }

    public function test_un_candidat_ne_peut_pas_consulter_la_candidature_d_un_autre(): void
    {
        $autreCandidat = Candidat::factory()->create();
        $candidature   = Candidature::factory()->pour($autreCandidat, $this->offre)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson("/api/candidat/candidatures/{$candidature->id_candidature}")
            ->assertForbidden();
    }

    // -------------------------------------------------------------------
    // RG14 — le recruteur ne voit que les candidatures de ses offres
    // -------------------------------------------------------------------

    public function test_un_recruteur_voit_les_candidatures_de_ses_offres(): void
    {
        Candidature::factory()->pour($this->candidat, $this->offre)->create();

        [, , $offreAutre] = $this->autreRecruteur();
        Candidature::factory()->pour(Candidat::factory()->create(), $offreAutre)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/candidatures')
            ->assertOk()
            ->assertJsonCount(1, 'candidatures')
            ->assertJsonPath('candidatures.0.id_offre', $this->offre->id_offre);
    }

    public function test_un_recruteur_ne_peut_pas_consulter_la_candidature_d_une_autre_offre(): void
    {
        [$autreUser] = $this->autreRecruteur();

        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($autreUser, 'sanctum')
            ->getJson("/api/recruteur/candidatures/{$candidature->id_candidature}")
            ->assertForbidden();
    }

    public function test_un_recruteur_ne_peut_pas_traiter_la_candidature_d_une_autre_offre(): void
    {
        [$autreUser] = $this->autreRecruteur();

        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($autreUser, 'sanctum')
            ->patchJson("/api/recruteur/candidatures/{$candidature->id_candidature}/statut", [
                'statut' => StatutCandidature::Refusee->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('candidatures', [
            'id_candidature' => $candidature->id_candidature,
            'statut'         => StatutCandidature::EnAttente->value,
        ]);
    }

    public function test_la_liste_du_recruteur_peut_etre_restreinte_a_une_offre(): void
    {
        $autreOffre = OffreEmploi::factory()->publieePar($this->recruteur, $this->offre->departement)->create();

        Candidature::factory()->pour($this->candidat, $this->offre)->create();
        Candidature::factory()->pour(Candidat::factory()->create(), $autreOffre)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/candidatures?id_offre='.$autreOffre->id_offre)
            ->assertOk()
            ->assertJsonCount(1, 'candidatures')
            ->assertJsonPath('candidatures.0.id_offre', $autreOffre->id_offre);
    }

    public function test_la_liste_du_recruteur_exige_une_authentification(): void
    {
        $this->getJson('/api/recruteur/candidatures')->assertUnauthorized();
    }

    public function test_un_candidat_ne_peut_pas_acceder_a_la_liste_du_recruteur(): void
    {
        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson('/api/recruteur/candidatures')
            ->assertForbidden();
    }

    // -------------------------------------------------------------------
    // Transitions de statut — RG32
    // -------------------------------------------------------------------

    public function test_un_recruteur_peut_faire_avancer_une_candidature(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/candidatures/{$candidature->id_candidature}/statut", [
                'statut'                => StatutCandidature::EnCours->value,
                'commentaire_recruteur' => 'Profil interessant, a examiner.',
            ])
            ->assertOk()
            ->assertJsonPath('candidature.statut', StatutCandidature::EnCours->value)
            ->assertJsonPath('candidature.commentaire_recruteur', 'Profil interessant, a examiner.');
    }

    public function test_une_transition_hors_cycle_est_refusee(): void
    {
        // En attente ne mene pas directement a acceptee : la preselection
        // est une etape obligatoire.
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/candidatures/{$candidature->id_candidature}/statut", [
                'statut' => StatutCandidature::Acceptee->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('statut');

        $this->assertDatabaseHas('candidatures', [
            'id_candidature' => $candidature->id_candidature,
            'statut'         => StatutCandidature::EnAttente->value,
        ]);
    }

    public function test_une_candidature_definitive_ne_change_plus_de_statut(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)
            ->statut(StatutCandidature::Refusee)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/candidatures/{$candidature->id_candidature}/statut", [
                'statut' => StatutCandidature::EnCours->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('statut');
    }

    public function test_une_decision_definitive_est_datee(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)
            ->statut(StatutCandidature::Preselectionnee)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/candidatures/{$candidature->id_candidature}/statut", [
                'statut' => StatutCandidature::Acceptee->value,
            ])
            ->assertOk()
            ->assertJsonPath('candidature.date_decision', now()->toDateString())
            ->assertJsonPath('candidature.statut_definitif', true);
    }

    public function test_la_reponse_expose_les_statuts_encore_possibles(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)
            ->statut(StatutCandidature::Preselectionnee)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson("/api/recruteur/candidatures/{$candidature->id_candidature}")
            ->assertOk()
            ->assertJsonCount(2, 'candidature.statuts_possibles');
    }

    public function test_un_statut_inconnu_est_refuse(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/candidatures/{$candidature->id_candidature}/statut", [
                'statut' => 'embauche_immediate',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('statut');
    }

    public function test_un_candidat_ne_peut_pas_changer_le_statut_de_sa_candidature(): void
    {
        $candidature = Candidature::factory()->pour($this->candidat, $this->offre)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->patchJson("/api/recruteur/candidatures/{$candidature->id_candidature}/statut", [
                'statut' => StatutCandidature::Acceptee->value,
            ])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------
    // Classement par score — RG43
    // -------------------------------------------------------------------

    public function test_les_candidatures_sont_classees_par_score_decroissant(): void
    {
        $faible = Candidature::factory()->pour(Candidat::factory()->create(), $this->offre)->score(41.5)->create();
        $fort   = Candidature::factory()->pour(Candidat::factory()->create(), $this->offre)->score(88.25)->create();
        $moyen  = Candidature::factory()->pour(Candidat::factory()->create(), $this->offre)->score(63)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/candidatures')
            ->assertOk()
            ->assertJsonPath('candidatures.0.id_candidature', $fort->id_candidature)
            ->assertJsonPath('candidatures.1.id_candidature', $moyen->id_candidature)
            ->assertJsonPath('candidatures.2.id_candidature', $faible->id_candidature)
            ->assertJsonPath('candidatures.0.score_final', 88.25);
    }

    public function test_les_candidatures_sans_score_passent_en_dernier(): void
    {
        $sansScore = Candidature::factory()->pour($this->candidat, $this->offre)->create();
        $avecScore = Candidature::factory()->pour(Candidat::factory()->create(), $this->offre)->score(50)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/candidatures')
            ->assertOk()
            ->assertJsonCount(2, 'candidatures')
            ->assertJsonPath('candidatures.0.id_candidature', $avecScore->id_candidature)
            ->assertJsonPath('candidatures.1.id_candidature', $sansScore->id_candidature)
            ->assertJsonPath('candidatures.1.score_final', null);
    }
}
