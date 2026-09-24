<?php

namespace Tests\Unit;

use App\Enums\ImportanceCompetence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs de l'enumeration ImportanceCompetence (RG21, RG40).
 *
 * Verifie que les poids attribues a chaque niveau d'importance sont
 * coherents et utilisables par le ScoringService : Essentielle doit
 * toujours peser plus qu'Importante, qui pese plus que Souhaitee.
 */
class ImportanceCompetenceTest extends TestCase
{
    // -----------------------------------------------------------------
    // Structure de l'enumeration
    // -----------------------------------------------------------------

    public function test_l_enumeration_expose_trois_niveaux(): void
    {
        $this->assertCount(3, ImportanceCompetence::cases());
    }

    public function test_les_valeurs_string_sont_stables(): void
    {
        $this->assertSame('essentielle', ImportanceCompetence::Essentielle->value);
        $this->assertSame('importante',  ImportanceCompetence::Importante->value);
        $this->assertSame('souhaitee',   ImportanceCompetence::Souhaitee->value);
    }

    public function test_la_methode_valeurs_renvoie_les_trois_valeurs(): void
    {
        $this->assertSame(
            ['essentielle', 'importante', 'souhaitee'],
            ImportanceCompetence::valeurs()
        );
    }

    // -----------------------------------------------------------------
    // Libelles affiches a l'utilisateur
    // -----------------------------------------------------------------

    #[DataProvider('libellesAttendus')]
    public function test_les_libelles_sont_corrects(ImportanceCompetence $importance, string $attendu): void
    {
        $this->assertSame($attendu, $importance->libelle());
    }

    public static function libellesAttendus(): array
    {
        return [
            'essentielle' => [ImportanceCompetence::Essentielle, 'Essentielle'],
            'importante'  => [ImportanceCompetence::Importante,  'Importante'],
            'souhaitee'   => [ImportanceCompetence::Souhaitee,   'Souhaitée'],
        ];
    }

    // -----------------------------------------------------------------
    // Poids numeriques — RG21, RG40
    // -----------------------------------------------------------------

    #[DataProvider('poidsAttendus')]
    public function test_les_poids_sont_corrects(ImportanceCompetence $importance, float $attendu): void
    {
        $this->assertSame($attendu, $importance->poids());
    }

    public static function poidsAttendus(): array
    {
        return [
            'essentielle vaut 3' => [ImportanceCompetence::Essentielle, 3.0],
            'importante vaut 2'  => [ImportanceCompetence::Importante,  2.0],
            'souhaitee vaut 1'   => [ImportanceCompetence::Souhaitee,   1.0],
        ];
    }

    // -----------------------------------------------------------------
    // Coherence de l'ordre des poids
    // -----------------------------------------------------------------

    public function test_essentielle_pese_plus_qu_importante(): void
    {
        $this->assertGreaterThan(
            ImportanceCompetence::Importante->poids(),
            ImportanceCompetence::Essentielle->poids()
        );
    }

    public function test_importante_pese_plus_que_souhaitee(): void
    {
        $this->assertGreaterThan(
            ImportanceCompetence::Souhaitee->poids(),
            ImportanceCompetence::Importante->poids()
        );
    }

    public function test_les_poids_sont_strictement_decroissants(): void
    {
        $poids = array_map(
            fn (ImportanceCompetence $i) => $i->poids(),
            ImportanceCompetence::cases()
        );

        // Essentielle > Importante > Souhaitee
        $this->assertGreaterThan($poids[1], $poids[0]);
        $this->assertGreaterThan($poids[2], $poids[1]);
    }

    // -----------------------------------------------------------------
    // Proprietes mathematiques des poids
    // -----------------------------------------------------------------

    public function test_tous_les_poids_sont_strictement_positifs(): void
    {
        foreach (ImportanceCompetence::cases() as $importance) {
            $this->assertGreaterThan(
                0.0,
                $importance->poids(),
                "Le poids de {$importance->value} doit etre strictement positif."
            );
        }
    }

    public function test_essentielle_vaut_le_triple_de_souhaitee(): void
    {
        // 3.0 / 1.0 = 3
        $ratio = ImportanceCompetence::Essentielle->poids()
               / ImportanceCompetence::Souhaitee->poids();

        $this->assertSame(3.0, $ratio);
    }

    public function test_importante_vaut_le_double_de_souhaitee(): void
    {
        $ratio = ImportanceCompetence::Importante->poids()
               / ImportanceCompetence::Souhaitee->poids();

        $this->assertSame(2.0, $ratio);
    }

    public function test_essentielle_vaut_1_5_fois_importante(): void
    {
        // 3.0 / 2.0 = 1.5
        $ratio = ImportanceCompetence::Essentielle->poids()
               / ImportanceCompetence::Importante->poids();

        $this->assertSame(1.5, $ratio);
    }

    public function test_la_somme_des_poids_vaut_six(): void
    {
        // 3.0 + 2.0 + 1.0 = 6.0 — utile pour verifier un eventuel ratio normalise.
        $somme = array_sum(array_map(
            fn (ImportanceCompetence $i) => $i->poids(),
            ImportanceCompetence::cases()
        ));

        $this->assertSame(6.0, $somme);
    }

    // -----------------------------------------------------------------
    // Cas metier : comment le ScoringService utilise ces poids
    // -----------------------------------------------------------------

    public function test_une_competence_essentielle_manquante_penalise_plus(): void
    {
        // RG41 : dans AnalyseIaService, une competence essentielle manquante
        // est plus penalisante qu'une competence souhaitee manquante.
        $this->assertGreaterThan(
            ImportanceCompetence::Souhaitee->poids(),
            ImportanceCompetence::Essentielle->poids()
        );
    }

    public function test_le_poids_est_compatible_avec_un_calcul_float(): void
    {
        // Verifie qu'un score pondere reste un float propre.
        $poids = ImportanceCompetence::Importante->poids();
        $resultat = 100.0 * $poids;

        $this->assertSame(200.0, $resultat);
        $this->assertIsFloat($resultat);
    }
}