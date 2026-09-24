<?php

namespace Tests\Unit;

use App\Enums\StatutOffre;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs de l'enumeration StatutOffre (RG18, RG27, RG30).
 *
 * Verifie que seule une offre ouverte accepte des candidatures, ce qui
 * conditionne la regle RG27 et evite tout depot sur une offre fermee.
 */
class StatutOffreTest extends TestCase
{
    // -----------------------------------------------------------------
    // Structure de l'enumeration
    // -----------------------------------------------------------------

    public function test_l_enumeration_expose_trois_statuts(): void
    {
        $this->assertCount(3, StatutOffre::cases());
    }

    public function test_les_valeurs_string_sont_stables(): void
    {
        $this->assertSame('ouverte',   StatutOffre::Ouverte->value);
        $this->assertSame('fermee',    StatutOffre::Fermee->value);
        $this->assertSame('suspendue', StatutOffre::Suspendue->value);
    }

    public function test_la_methode_valeurs_renvoie_les_trois_valeurs(): void
    {
        $this->assertSame(
            ['ouverte', 'fermee', 'suspendue'],
            StatutOffre::valeurs()
        );
    }

    // -----------------------------------------------------------------
    // Libelles
    // -----------------------------------------------------------------

    #[DataProvider('libellesAttendus')]
    public function test_les_libelles_sont_corrects(StatutOffre $statut, string $attendu): void
    {
        $this->assertSame($attendu, $statut->libelle());
    }

    public static function libellesAttendus(): array
    {
        return [
            'ouverte'   => [StatutOffre::Ouverte,   'Ouverte'],
            'fermee'    => [StatutOffre::Fermee,    'Fermée'],
            'suspendue' => [StatutOffre::Suspendue, 'Suspendue'],
        ];
    }

    // -----------------------------------------------------------------
    // Acceptation des candidatures — RG27, RG30
    // -----------------------------------------------------------------

    #[DataProvider('acceptationCandidatures')]
    public function test_seule_une_offre_ouverte_accepte_les_candidatures(
        StatutOffre $statut,
        bool $attendu
    ): void {
        $this->assertSame($attendu, $statut->accepteCandidatures());
    }

    public static function acceptationCandidatures(): array
    {
        return [
            'ouverte accepte'      => [StatutOffre::Ouverte,   true],
            'fermee refuse'        => [StatutOffre::Fermee,    false],
            'suspendue refuse'     => [StatutOffre::Suspendue, false],
        ];
    }

    // -----------------------------------------------------------------
    // Coherence : un seul statut accepte
    // -----------------------------------------------------------------

    public function test_il_n_y_a_qu_un_seul_statut_qui_accepte(): void
    {
        $acceptants = array_filter(
            StatutOffre::cases(),
            fn (StatutOffre $s) => $s->accepteCandidatures()
        );

        $this->assertCount(1, $acceptants);
    }

    public function test_ouverte_est_le_seul_statut_acceptant(): void
    {
        $acceptant = array_values(array_filter(
            StatutOffre::cases(),
            fn (StatutOffre $s) => $s->accepteCandidatures()
        ))[0];

        $this->assertSame(StatutOffre::Ouverte, $acceptant);
    }

    // -----------------------------------------------------------------
    // Cas metier typiques
    // -----------------------------------------------------------------

    public function test_une_offre_fermee_n_accepte_plus_de_candidatures(): void
    {
        // RG27 : une offre fermee ne doit plus apparaitre aux candidats.
        $this->assertFalse(StatutOffre::Fermee->accepteCandidatures());
    }

    public function test_une_offre_suspendue_n_accepte_pas_de_candidatures(): void
    {
        // RG30 : suspension temporaire — aucune candidature pendant ce temps.
        $this->assertFalse(StatutOffre::Suspendue->accepteCandidatures());
    }
}