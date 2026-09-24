<?php

namespace Tests\Feature;

use App\Enums\StatutResultatTest;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\NotificationApp;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\ResultatTest;
use App\Models\TestTechnique;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestTechniqueTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateurRecruteur;

    private Recruteur $recruteur;

    private Candidature $candidature;

    private User $utilisateurCandidat;

    private TestTechnique $test;

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
        $candidat = Candidat::factory()->create(['id_user' => $this->utilisateurCandidat->id]);
        $this->utilisateurCandidat->refresh();

        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $departement)->create();

        $this->candidature = Candidature::factory()->pour($candidat, $offre)->create();
        $this->test        = TestTechnique::factory()->create(['score_max' => 100]);
    }

    private function autreRecruteur(): User
    {
        $entreprise = Entreprise::factory()->create();
        $user       = User::factory()->recruteur()->create();

        Recruteur::factory()->create([
            'id_user'       => $user->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);

        return $user->refresh();
    }

    // -------------------------------------------------------------------
    // Catalogue — RG45
    // -------------------------------------------------------------------

    public function test_un_recruteur_peut_consulter_le_catalogue(): void
    {
        TestTechnique::factory()->count(3)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/tests')
            ->assertOk()
            ->assertJsonCount(4, 'tests');
    }

    public function test_un_candidat_ne_consulte_pas_le_catalogue(): void
    {
        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson('/api/tests')
            ->assertForbidden();
    }

    public function test_un_administrateur_peut_creer_un_test(): void
    {
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/tests', [
                'titre'     => 'Algorithmique',
                'duree'     => 60,
                'score_max' => 20,
            ])
            ->assertCreated()
            ->assertJsonPath('test.titre', 'Algorithmique');
    }

    public function test_un_recruteur_ne_peut_pas_creer_de_test(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/tests', ['titre' => 'Interdit', 'duree' => 30])
            ->assertForbidden();
    }

    public function test_un_recruteur_rattache_des_tests_a_son_offre(): void
    {
        $autreTest = TestTechnique::factory()->create();
        $offre = $this->candidature->offre;

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->putJson("/api/recruteur/offres/{$offre->id_offre}/tests", [
                'tests' => [$this->test->id_test, $autreTest->id_test],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'offre.tests');

        $this->assertDatabaseCount('proposer', 2);
    }

    public function test_un_recruteur_ne_rattache_pas_de_tests_a_l_offre_d_un_autre(): void
    {
        $offre = $this->candidature->offre;

        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->putJson("/api/recruteur/offres/{$offre->id_offre}/tests", [
                'tests' => [$this->test->id_test],
            ])
            ->assertForbidden();
    }

    public function test_la_liste_vide_retire_les_tests_d_une_offre(): void
    {
        $offre = $this->candidature->offre;
        $offre->tests()->attach($this->test);

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->putJson("/api/recruteur/offres/{$offre->id_offre}/tests", ['tests' => []])
            ->assertOk()
            ->assertJsonCount(0, 'offre.tests');

        $this->assertDatabaseCount('proposer', 0);
    }

    public function test_un_test_avec_resultats_ne_peut_pas_etre_supprime(): void
    {
        ResultatTest::factory()->pour($this->candidature, $this->test)->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->deleteJson("/api/tests/{$this->test->id_test}")
            ->assertStatus(409);

        $this->assertDatabaseHas('tests_techniques', ['id_test' => $this->test->id_test]);
    }

    public function test_la_creation_exige_un_titre_et_une_duree(): void
    {
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/tests', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titre', 'duree']);
    }

    // -------------------------------------------------------------------
    // Envoi au candidat — RG44, RG45
    // -------------------------------------------------------------------

    public function test_un_recruteur_envoie_un_test_au_candidat(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/tests/{$this->test->id_test}")
            ->assertCreated()
            ->assertJsonPath('resultat.statut', StatutResultatTest::Envoye->value);

        $this->assertDatabaseHas('resultats_tests', [
            'id_candidature' => $this->candidature->id_candidature,
            'id_test'        => $this->test->id_test,
        ]);
    }

    public function test_l_envoi_d_un_test_notifie_le_candidat(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/tests/{$this->test->id_test}")
            ->assertCreated();

        // RG44 — le candidat est avise.
        $this->assertDatabaseHas('notifications_app', [
            'id_user' => $this->utilisateurCandidat->id,
            'lu'      => false,
        ]);
    }

    public function test_un_test_n_est_envoye_qu_une_fois_par_candidature(): void
    {
        foreach (range(1, 2) as $ignore) {
            $this->actingAs($this->utilisateurRecruteur, 'sanctum')
                ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/tests/{$this->test->id_test}")
                ->assertCreated();
        }

        $this->assertDatabaseCount('resultats_tests', 1);
    }

    public function test_un_recruteur_n_envoie_pas_de_test_sur_la_candidature_d_un_autre(): void
    {
        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/tests/{$this->test->id_test}")
            ->assertForbidden();

        $this->assertDatabaseCount('resultats_tests', 0);
    }

    // -------------------------------------------------------------------
    // Score — RG45
    // -------------------------------------------------------------------

    public function test_un_recruteur_enregistre_le_score_obtenu(): void
    {
        $resultat = ResultatTest::factory()->pour($this->candidature, $this->test)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/resultats-tests/{$resultat->id_resultat}/score", [
                'score_obtenu' => 82,
                'commentaire'  => 'Bon niveau algorithmique.',
            ])
            ->assertOk()
            ->assertJsonPath('resultat.statut', StatutResultatTest::Termine->value)
            ->assertJsonPath('resultat.score_obtenu', 82)
            ->assertJsonPath('resultat.pourcentage', 82);
    }

    public function test_le_score_ne_peut_pas_depasser_le_bareme(): void
    {
        $test     = TestTechnique::factory()->create(['score_max' => 20]);
        $resultat = ResultatTest::factory()->pour($this->candidature, $test)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/resultats-tests/{$resultat->id_resultat}/score", ['score_obtenu' => 25])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('score_obtenu');
    }

    public function test_le_pourcentage_ramene_le_score_sur_cent(): void
    {
        // Bareme de 30 : le rapport n'est pas entier, ce qui verifie que la
        // conversion ne se contente pas de recopier le score brut.
        $test     = TestTechnique::factory()->create(['score_max' => 30]);
        $resultat = ResultatTest::factory()->pour($this->candidature, $test)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/resultats-tests/{$resultat->id_resultat}/score", ['score_obtenu' => 20])
            ->assertOk()
            ->assertJsonPath('resultat.pourcentage', 66.67);
    }

    public function test_un_test_non_termine_n_a_pas_de_pourcentage(): void
    {
        $resultat = ResultatTest::factory()->pour($this->candidature, $this->test)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/resultats-tests')
            ->assertOk()
            ->assertJsonPath('resultats.0.pourcentage', null);
    }

    public function test_un_recruteur_ne_voit_que_les_resultats_de_ses_offres(): void
    {
        ResultatTest::factory()->pour($this->candidature, $this->test)->create();

        // RG14 — la propriete de la candidature gouverne celle du resultat.
        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->getJson('/api/recruteur/resultats-tests')
            ->assertOk()
            ->assertJsonCount(0, 'resultats');

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/resultats-tests')
            ->assertOk()
            ->assertJsonCount(1, 'resultats');
    }

    public function test_le_candidat_consulte_les_tests_de_sa_candidature(): void
    {
        ResultatTest::factory()->pour($this->candidature, $this->test)->termine(90)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson("/api/candidat/candidatures/{$this->candidature->id_candidature}/tests")
            ->assertOk()
            ->assertJsonCount(1, 'resultats')
            ->assertJsonPath('resultats.0.score_obtenu', 90);
    }

    // -------------------------------------------------------------------
    // Notifications — RG44
    // -------------------------------------------------------------------

    public function test_un_utilisateur_ne_voit_que_ses_notifications(): void
    {
        NotificationApp::factory()->count(2)->pour($this->utilisateurCandidat)->create();
        NotificationApp::factory()->pour($this->utilisateurRecruteur)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'notifications')
            ->assertJsonPath('non_lues', 2);
    }

    public function test_les_notifications_non_lues_apparaissent_en_tete(): void
    {
        NotificationApp::factory()->pour($this->utilisateurCandidat)->lue()->create();
        $nonLue = NotificationApp::factory()->pour($this->utilisateurCandidat)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('notifications.0.id_notification', $nonLue->id_notification);
    }

    public function test_la_liste_peut_etre_restreinte_aux_non_lues(): void
    {
        NotificationApp::factory()->pour($this->utilisateurCandidat)->lue()->create();
        NotificationApp::factory()->pour($this->utilisateurCandidat)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson('/api/notifications?non_lues=1')
            ->assertOk()
            ->assertJsonCount(1, 'notifications');
    }

    public function test_un_utilisateur_marque_une_notification_comme_lue(): void
    {
        $notification = NotificationApp::factory()->pour($this->utilisateurCandidat)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id_notification}/lue")
            ->assertOk()
            ->assertJsonPath('notification.lu', true);
    }

    public function test_un_utilisateur_ne_marque_pas_la_notification_d_un_autre(): void
    {
        $notification = NotificationApp::factory()->pour($this->utilisateurRecruteur)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id_notification}/lue")
            ->assertForbidden();
    }

    public function test_un_utilisateur_marque_toutes_ses_notifications_comme_lues(): void
    {
        NotificationApp::factory()->count(3)->pour($this->utilisateurCandidat)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->postJson('/api/notifications/toutes-lues')
            ->assertOk()
            ->assertJsonPath('non_lues', 0);

        $this->assertSame(0, NotificationApp::where('id_user', $this->utilisateurCandidat->id)->where('lu', false)->count());
    }

    public function test_la_liste_des_notifications_exige_une_authentification(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }
}
