<?php

namespace Tests\Unit;

use App\Enums\Recommandation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs de l'énumération Recommandation (RG42).
 *
 * Vérifie que les seuils de décision de l'IA sont stables, explicables
 * et reproductibles — aucune base de données, aucun conteneur Laravel.
 */
class RecommandationTest extends TestCase
{
    // -----------------------------------------------------------------
    // Les trois cases de l'énumération
    // -----------------------------------------------------------------

    public function test_l_enumeration_expose_trois_cases(): void
    {
        $this->assertCount(3, Recommandation::cases());
    }

    public function test_les_valeurs_de_l_enumeration_sont_stables(): void
    {
        $this->assertSame('retenir', Recommandation::Retenir->value);
        $this->assertSame('a_examiner', Recommandation::AExaminer->value);
        $this->assertSame('rejeter', Recommandation::Rejeter->value);
    }

    public function test_la_methode_valeurs_renvoie_les_trois_valeurs(): void
    {
        $this->assertSame(
            ['retenir', 'a_examiner', 'rejeter'],
            Recommandation::valeurs()
        );
    }

    // -----------------------------------------------------------------
    // Seuils de décision — RG42
    // -----------------------------------------------------------------

    #[DataProvider('seuilsDeDecision')]
    public function test_depuis_score_respecte_les_seuils(float $score, Recommandation $attendu): void
    {
        $this->assertSame($attendu, Recommandation::depuisScore($score));
    }

    public static function seuilsDeDecision(): array
    {
        return [
            'score 100 vers retenir'          => [100.0, Recommandation::Retenir],
            'score 85 vers retenir'           => [85.0, Recommandation::Retenir],
            'score 70 limite vers retenir'    => [70.0, Recommandation::Retenir],
            'score 69.99 vers a examiner'     => [69.99, Recommandation::AExaminer],
            'score 60 vers a examiner'        => [60.0, Recommandation::AExaminer],
            'score 45 limite vers a examiner' => [45.0, Recommandation::AExaminer],
            'score 44.99 vers rejeter'        => [44.99, Recommandation::Rejeter],
            'score 20 vers rejeter'           => [20.0, Recommandation::Rejeter],
            'score 0 vers rejeter'            => [0.0, Recommandation::Rejeter],
        ];
    }

    // -----------------------------------------------------------------
    // Cas limites / robustesse
    // -----------------------------------------------------------------

    public function test_un_score_negatif_est_rejete(): void
    {
        $this->assertSame(Recommandation::Rejeter, Recommandation::depuisScore(-10.0));
    }

    public function test_un_score_superieur_a_100_est_retenu(): void
    {
        $this->assertSame(Recommandation::Retenir, Recommandation::depuisScore(150.0));
    }

    // -----------------------------------------------------------------
    // Libellés affichés à l'utilisateur
    // -----------------------------------------------------------------

    public function test_les_libelles_sont_en_francais(): void
    {
        $this->assertSame('À retenir', Recommandation::Retenir->libelle());
        $this->assertSame('À examiner', Recommandation::AExaminer->libelle());
        $this->assertSame('À rejeter', Recommandation::Rejeter->libelle());
    }
}
