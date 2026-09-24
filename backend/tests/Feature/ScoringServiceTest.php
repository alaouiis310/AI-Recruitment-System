<?php

namespace Tests\Feature;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Enums\NiveauEtude;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Competence;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Le calcul du score est deterministe : aucune de ces assertions ne depend d'un modele de langage (RG40). */
class ScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScoringService $scoring;

    private Departement $departement;

    private Recruteur $recruteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scoring = app(ScoringService::class);

        $entreprise        = Entreprise::factory()->create();
        $this->departement = Departement::factory()->pour($entreprise)->create();
        $this->recruteur   = Recruteur::factory()->create(['id_entreprise' => $entreprise->id_entreprise]);
    }

    /** Construit une candidature avec les competences voulues de part et d'autre. */
    private function candidature(array $exigees, array $declarees, array $attributsOffre = [], array $attributsCandidat = []): Candidature
    {
        $offre = OffreEmploi::factory()
            ->publieePar($this->recruteur, $this->departement)
            ->create($attributsOffre + ['experience_min' => 0, 'niveau_etude' => null]);

        $candidat = Candidat::factory()->create($attributsCandidat);

        $offre->competences()->sync($exigees);
        $candidat->competences()->sync($declarees);

        return Candidature::factory()->pour($candidat, $offre)->create();
    }

    /** Attributs du pivot requerir. */
    private function exige(NiveauCompetence $niveau, ImportanceCompetence $importance): array
    {
        return ['niveau_requis' => $niveau->value, 'importance' => $importance->value];
    }

    /** Attributs du pivot posseder. */
    private function declare(NiveauCompetence $niveau, float $annees = 0): array
    {
        return ['niveau' => $niveau->value, 'annees_experience' => $annees];
    }

    public function test_un_profil_parfait_obtient_cent(): void
    {
        $php = Competence::factory()->create();

        $candidature = $this->candidature(
            [$php->id_competence => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Essentielle)],
            [$php->id_competence => $this->declare(NiveauCompetence::Avance, 3)],
        );

        $scores = $this->scoring->evaluer($candidature);

        $this->assertSame(100.0, $scores['score_matching']);
        $this->assertSame([], $scores['competences_manquantes']);
    }

    public function test_depasser_le_niveau_exige_ne_penalise_pas(): void
    {
        $php = Competence::factory()->create();

        $candidature = $this->candidature(
            [$php->id_competence => $this->exige(NiveauCompetence::Intermediaire, ImportanceCompetence::Essentielle)],
            [$php->id_competence => $this->declare(NiveauCompetence::Expert, 5)],
        );

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_competence']);
    }

    public function test_une_competence_absente_annule_son_poids(): void
    {
        $php   = Competence::factory()->create();
        $mysql = Competence::factory()->create();

        // Deux competences de meme importance : il en manque la moitie.
        $candidature = $this->candidature(
            [
                $php->id_competence   => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Essentielle),
                $mysql->id_competence => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Essentielle),
            ],
            [$php->id_competence => $this->declare(NiveauCompetence::Avance, 3)],
        );

        $scores = $this->scoring->evaluer($candidature);

        $this->assertSame(50.0, $scores['score_competence']);
        $this->assertCount(1, $scores['competences_manquantes']);
        $this->assertSame($mysql->nom, $scores['competences_manquantes'][0]['nom']);
        $this->assertNull($scores['competences_manquantes'][0]['niveau_actuel']);
    }

    public function test_un_cran_de_niveau_manquant_retire_un_quart_des_points(): void
    {
        $php = Competence::factory()->create();

        $candidature = $this->candidature(
            [$php->id_competence => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Essentielle)],
            [$php->id_competence => $this->declare(NiveauCompetence::Intermediaire, 2)],
        );

        $scores = $this->scoring->evaluer($candidature);

        $this->assertSame(75.0, $scores['score_competence']);
        $this->assertCount(1, $scores['competences_manquantes']);
        $this->assertSame(NiveauCompetence::Intermediaire->value, $scores['competences_manquantes'][0]['niveau_actuel']);
    }

    public function test_une_competence_essentielle_pese_plus_qu_une_souhaitee(): void
    {
        $essentielle = Competence::factory()->create();
        $souhaitee   = Competence::factory()->create();

        // Poids 3 contre 1 : ne couvrir que la souhaitee vaut 1/4 des points.
        $candidature = $this->candidature(
            [
                $essentielle->id_competence => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Essentielle),
                $souhaitee->id_competence   => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Souhaitee),
            ],
            [$souhaitee->id_competence => $this->declare(NiveauCompetence::Avance, 1)],
        );

        $this->assertSame(25.0, $this->scoring->evaluer($candidature)['score_competence']);
    }

    public function test_une_offre_sans_competence_exigee_neutralise_le_volet(): void
    {
        $candidature = $this->candidature([], []);

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_competence']);
    }

    public function test_l_experience_exigee_atteinte_vaut_le_volet_entier(): void
    {
        $candidature = $this->candidature([], [], ['experience_min' => 3], ['experience_totale' => 3]);

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_experience']);
    }

    public function test_l_experience_est_proportionnelle_en_deca_du_seuil(): void
    {
        $candidature = $this->candidature([], [], ['experience_min' => 4], ['experience_totale' => 2]);

        $this->assertSame(50.0, $this->scoring->evaluer($candidature)['score_experience']);
    }

    public function test_depasser_l_experience_exigee_ne_donne_pas_de_bonus(): void
    {
        $candidature = $this->candidature([], [], ['experience_min' => 2], ['experience_totale' => 10]);

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_experience']);
    }

    public function test_une_offre_sans_experience_exigee_neutralise_le_volet(): void
    {
        $candidature = $this->candidature([], [], ['experience_min' => 0], ['experience_totale' => 0]);

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_experience']);
    }

    public function test_le_diplome_au_niveau_exige_vaut_le_volet_entier(): void
    {
        $candidature = $this->candidature([], [],
            ['niveau_etude' => NiveauEtude::Bac5],
            ['diplome' => "Diplôme d'ingénieur en génie informatique"],
        );

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_diplome']);
    }

    public function test_chaque_annee_manquante_retire_vingt_points(): void
    {
        // Licence (bac+3) face a une exigence bac+5 : deux annees manquantes.
        $candidature = $this->candidature([], [],
            ['niveau_etude' => NiveauEtude::Bac5],
            ['diplome' => 'Licence en développement web'],
        );

        $this->assertSame(60.0, $this->scoring->evaluer($candidature)['score_diplome']);
    }

    public function test_la_notation_bac_plus_n_est_reconnue(): void
    {
        $candidature = $this->candidature([], [],
            ['niveau_etude' => NiveauEtude::Bac5],
            ['diplome' => 'Bac +2 en informatique'],
        );

        $this->assertSame(40.0, $this->scoring->evaluer($candidature)['score_diplome']);
    }

    public function test_un_diplome_illisible_neutralise_le_volet(): void
    {
        // Le volet ne doit pas penaliser sur un critere que la base ne permet pas de trancher.
        $candidature = $this->candidature([], [],
            ['niveau_etude' => NiveauEtude::Bac5],
            ['diplome' => 'Formation interne maison'],
        );

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_diplome']);
    }

    public function test_une_offre_sans_niveau_exige_neutralise_le_volet(): void
    {
        $candidature = $this->candidature([], [], ['niveau_etude' => null], ['diplome' => null]);

        $this->assertSame(100.0, $this->scoring->evaluer($candidature)['score_diplome']);
    }

    public function test_le_score_global_est_la_moyenne_ponderee_des_trois_volets(): void
    {
        $php = Competence::factory()->create();

        // Competences 50, experience 50, diplome 100.
        $mysql = Competence::factory()->create();

        $candidature = $this->candidature(
            [
                $php->id_competence   => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Essentielle),
                $mysql->id_competence => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Essentielle),
            ],
            [$php->id_competence => $this->declare(NiveauCompetence::Avance, 2)],
            ['experience_min' => 4, 'niveau_etude' => null],
            ['experience_totale' => 2],
        );

        $scores = $this->scoring->evaluer($candidature);

        $this->assertSame(50.0, $scores['score_competence']);
        $this->assertSame(50.0, $scores['score_experience']);
        $this->assertSame(100.0, $scores['score_diplome']);
        $this->assertSame(57.5, $scores['score_matching']);
    }

    public function test_tous_les_scores_restent_entre_zero_et_cent(): void
    {
        $php = Competence::factory()->create();

        $candidature = $this->candidature(
            [$php->id_competence => $this->exige(NiveauCompetence::Expert, ImportanceCompetence::Essentielle)],
            [$php->id_competence => $this->declare(NiveauCompetence::Debutant, 0)],
            ['experience_min' => 10, 'niveau_etude' => NiveauEtude::Bac8],
            ['experience_totale' => 0, 'diplome' => 'Baccalauréat'],
        );

        // Le pire profil possible reste borne (RG39).
        foreach ($this->scoring->evaluer($candidature) as $cle => $valeur) {
            if ($cle === 'competences_manquantes') {
                continue;
            }

            $this->assertGreaterThanOrEqual(0, $valeur, "{$cle} sous zero");
            $this->assertLessThanOrEqual(100, $valeur, "{$cle} au-dessus de cent");
        }
    }

    public function test_le_calcul_est_reproductible(): void
    {
        $php = Competence::factory()->create();

        $candidature = $this->candidature(
            [$php->id_competence => $this->exige(NiveauCompetence::Avance, ImportanceCompetence::Importante)],
            [$php->id_competence => $this->declare(NiveauCompetence::Intermediaire, 2)],
            ['experience_min' => 3, 'niveau_etude' => NiveauEtude::Bac3],
            ['experience_totale' => 2, 'diplome' => 'Licence'],
        );

        // Les memes entrees produisent toujours le meme score (RG40).
        $premier = $this->scoring->evaluer($candidature);
        $second  = $this->scoring->evaluer($candidature->fresh());

        $this->assertSame($premier, $second);
    }
}
