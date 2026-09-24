<?php

namespace Tests\Feature;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Enums\Recommandation;
use App\Jobs\AnalyseCandidatureJob;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Competence;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use App\Services\AnalyseIaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** RG37 a RG42. */
class AnalyseIaTest extends TestCase
{
    use RefreshDatabase;

    private Departement $departement;

    private Recruteur $recruteur;

    private User $utilisateurRecruteur;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ia.cle_api' => null]);

        $entreprise        = Entreprise::factory()->create();
        $this->departement = Departement::factory()->pour($entreprise)->create();

        $this->utilisateurRecruteur = User::factory()->recruteur()->create();
        $this->recruteur = Recruteur::factory()->create([
            'id_user'       => $this->utilisateurRecruteur->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
        $this->utilisateurRecruteur->refresh();
    }

    /** Candidature dont le profil couvre exactement les exigences. */
    private function candidatureCouvrante(): Candidature
    {
        $php = Competence::factory()->create();

        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)
            ->create(['experience_min' => 2, 'niveau_etude' => null]);
        $offre->competences()->sync([
            $php->id_competence => [
                'niveau_requis' => NiveauCompetence::Avance->value,
                'importance'    => ImportanceCompetence::Essentielle->value,
            ],
        ]);

        $candidat = Candidat::factory()->create(['experience_totale' => 3]);
        $candidat->competences()->sync([
            $php->id_competence => ['niveau' => NiveauCompetence::Avance->value, 'annees_experience' => 3],
        ]);

        return Candidature::factory()->pour($candidat, $offre)->create();
    }

    public function test_le_depot_d_une_candidature_met_l_analyse_en_file(): void
    {
        Queue::fake();

        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();

        $utilisateur = User::factory()->create();
        Candidat::factory()->create(['id_user' => $utilisateur->id]);

        $this->actingAs($utilisateur->refresh(), 'sanctum')
            ->postJson('/api/candidat/candidatures', ['id_offre' => $offre->id_offre])
            ->assertCreated();

        Queue::assertPushed(AnalyseCandidatureJob::class);
    }

    public function test_le_job_ignore_une_candidature_supprimee(): void
    {
        $candidature = $this->candidatureCouvrante();
        $id          = $candidature->id_candidature;
        $candidature->delete();

        // Ne doit pas lever : la candidature a pu etre retiree entre-temps.
        (new AnalyseCandidatureJob($id))->handle(app(AnalyseIaService::class));

        $this->assertDatabaseCount('analyses_ia', 0);
    }

    public function test_l_analyse_aboutit_sans_cle_d_api(): void
    {
        $candidature = $this->candidatureCouvrante();

        $analyse = app(AnalyseIaService::class)->analyser($candidature);

        // Le score est calcule, le resume redige en PHP.
        $this->assertSame(100.0, (float) $analyse->score_matching);
        $this->assertNotNull($analyse->resume_cv);
        $this->assertNotEmpty($analyse->resume_cv);
        $this->assertSame(Recommandation::Retenir, $analyse->recommandation);
    }

    public function test_l_analyse_reporte_le_score_sur_la_candidature(): void
    {
        $candidature = $this->candidatureCouvrante();

        app(AnalyseIaService::class)->analyser($candidature);

        // Le classement du recruteur s'appuie sur cette colonne (RG43).
        $this->assertSame(100.0, (float) $candidature->fresh()->score_final);
    }

    public function test_une_candidature_ne_recoit_qu_une_seule_analyse(): void
    {
        $candidature = $this->candidatureCouvrante();
        $service     = app(AnalyseIaService::class);

        $premiere = $service->analyser($candidature);
        $seconde  = $service->analyser($candidature->fresh());

        // La relance remplace l'analyse, elle n'en ajoute pas (RG38).
        $this->assertSame($premiere->id_analyse, $seconde->id_analyse);
        $this->assertDatabaseCount('analyses_ia', 1);
    }

    public function test_l_analyse_liste_les_competences_manquantes(): void
    {
        $php   = Competence::factory()->create(['nom' => 'PHP']);
        $mysql = Competence::factory()->create(['nom' => 'MySQL']);

        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)
            ->create(['experience_min' => 0, 'niveau_etude' => null]);
        $offre->competences()->sync([
            $php->id_competence   => ['niveau_requis' => NiveauCompetence::Avance->value, 'importance' => ImportanceCompetence::Essentielle->value],
            $mysql->id_competence => ['niveau_requis' => NiveauCompetence::Avance->value, 'importance' => ImportanceCompetence::Essentielle->value],
        ]);

        $candidat = Candidat::factory()->create();
        $candidat->competences()->sync([
            $php->id_competence => ['niveau' => NiveauCompetence::Avance->value, 'annees_experience' => 3],
        ]);

        $analyse = app(AnalyseIaService::class)
            ->analyser(Candidature::factory()->pour($candidat, $offre)->create());

        // MySQL est exigee mais non declaree (RG41).
        $this->assertCount(1, $analyse->competences_manquantes);
        $this->assertSame('MySQL', $analyse->competences_manquantes[0]['nom']);
    }

    public function test_la_recommandation_decoule_des_seuils_de_score(): void
    {
        $this->assertSame(Recommandation::Retenir, Recommandation::depuisScore(70.0));
        $this->assertSame(Recommandation::Retenir, Recommandation::depuisScore(92.5));
        $this->assertSame(Recommandation::AExaminer, Recommandation::depuisScore(69.9));
        $this->assertSame(Recommandation::AExaminer, Recommandation::depuisScore(45.0));
        $this->assertSame(Recommandation::Rejeter, Recommandation::depuisScore(44.9));
        $this->assertSame(Recommandation::Rejeter, Recommandation::depuisScore(0.0));
    }

    public function test_un_profil_faible_est_rejete(): void
    {
        $php = Competence::factory()->create();

        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)
            ->create(['experience_min' => 8, 'niveau_etude' => null]);
        $offre->competences()->sync([
            $php->id_competence => ['niveau_requis' => NiveauCompetence::Expert->value, 'importance' => ImportanceCompetence::Essentielle->value],
        ]);

        $candidat = Candidat::factory()->create(['experience_totale' => 0]);

        $analyse = app(AnalyseIaService::class)
            ->analyser(Candidature::factory()->pour($candidat, $offre)->create());

        $this->assertSame(Recommandation::Rejeter, $analyse->recommandation);
    }

    public function test_le_recruteur_consulte_l_analyse_de_sa_candidature(): void
    {
        $candidature = $this->candidatureCouvrante();
        app(AnalyseIaService::class)->analyser($candidature);

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson("/api/recruteur/candidatures/{$candidature->id_candidature}/analyse")
            ->assertOk()
            ->assertJsonStructure([
                'analyse' => [
                    'score_matching', 'score_competence', 'score_experience', 'score_diplome',
                    'competences_manquantes', 'resume_cv', 'recommandation', 'date_analyse',
                ],
            ])
            ->assertJsonPath('analyse.recommandation', Recommandation::Retenir->value);
    }

    public function test_une_candidature_non_analysee_retourne_404(): void
    {
        $candidature = $this->candidatureCouvrante();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson("/api/recruteur/candidatures/{$candidature->id_candidature}/analyse")
            ->assertNotFound()
            ->assertJsonPath('analyse', null);
    }

    public function test_un_recruteur_ne_consulte_pas_l_analyse_d_une_autre_offre(): void
    {
        $candidature = $this->candidatureCouvrante();
        app(AnalyseIaService::class)->analyser($candidature);

        $autreEntreprise = Entreprise::factory()->create();
        $autreUser       = User::factory()->recruteur()->create();
        Recruteur::factory()->create([
            'id_user'       => $autreUser->id,
            'id_entreprise' => $autreEntreprise->id_entreprise,
        ]);

        // La propriete de la candidature gouverne aussi son analyse (RG14).
        $this->actingAs($autreUser->refresh(), 'sanctum')
            ->getJson("/api/recruteur/candidatures/{$candidature->id_candidature}/analyse")
            ->assertForbidden();
    }

    public function test_le_candidat_consulte_l_analyse_de_sa_propre_candidature(): void
    {
        $candidature = $this->candidatureCouvrante();
        app(AnalyseIaService::class)->analyser($candidature);

        $utilisateur = $candidature->candidat->user;

        $this->actingAs($utilisateur->refresh(), 'sanctum')
            ->getJson("/api/candidat/candidatures/{$candidature->id_candidature}/analyse")
            ->assertOk();
    }

    public function test_le_recruteur_peut_relancer_l_analyse(): void
    {
        Queue::fake();

        $candidature = $this->candidatureCouvrante();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$candidature->id_candidature}/analyse")
            ->assertStatus(202);

        Queue::assertPushed(AnalyseCandidatureJob::class);
    }

    public function test_la_consultation_de_l_analyse_exige_une_authentification(): void
    {
        $candidature = $this->candidatureCouvrante();

        $this->getJson("/api/recruteur/candidatures/{$candidature->id_candidature}/analyse")
            ->assertUnauthorized();
    }
}
