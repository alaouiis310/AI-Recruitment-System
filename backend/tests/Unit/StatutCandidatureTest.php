<?php

namespace Tests\Unit;

use App\Enums\StatutCandidature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs du cycle de vie des candidatures (RG32).
 *
 * Verifie que les transitions autorisees entre statuts sont stables et
 * qu'une decision definitive (acceptee ou refusee) ne peut plus etre revue.
 */
class StatutCandidatureTest extends TestCase
{
    // -----------------------------------------------------------------
    // Structure de l'enumeration
    // -----------------------------------------------------------------

    public function test_l_enumeration_expose_cinq_statuts(): void
    {
        $this->assertCount(5, StatutCandidature::cases());
    }

    public function test_les_valeurs_string_sont_stables(): void
    {
        $this->assertSame('en_attente', StatutCandidature::EnAttente->value);
        $this->assertSame('en_cours', StatutCandidature::EnCours->value);
        $this->assertSame('preselectionnee', StatutCandidature::Preselectionnee->value);
        $this->assertSame('acceptee', StatutCandidature::Acceptee->value);
        $this->assertSame('refusee', StatutCandidature::Refusee->value);
    }

    public function test_la_methode_valeurs_renvoie_les_cinq_valeurs(): void
    {
        $this->assertSame(
            ['en_attente', 'en_cours', 'preselectionnee', 'acceptee', 'refusee'],
            StatutCandidature::valeurs()
        );
    }

    // -----------------------------------------------------------------
    // Libelles
    // -----------------------------------------------------------------

    #[DataProvider('libellesAttendus')]
    public function test_les_libelles_sont_corrects(StatutCandidature $statut, string $attendu): void
    {
        $this->assertSame($attendu, $statut->libelle());
    }

    public static function libellesAttendus(): array
    {
        return [
            'en attente'        => [StatutCandidature::EnAttente,       'En attente'],
            'en cours'          => [StatutCandidature::EnCours,         "En cours d'examen"],
            'preselectionnee'   => [StatutCandidature::Preselectionnee, 'Présélectionnée'],
            'acceptee'          => [StatutCandidature::Acceptee,        'Acceptée'],
            'refusee'           => [StatutCandidature::Refusee,         'Refusée'],
        ];
    }

    // -----------------------------------------------------------------
    // Transitions autorisees — RG32
    // -----------------------------------------------------------------

    #[DataProvider('transitionsAutorisees')]
    public function test_les_transitions_autorisees_sont_correctes(
        StatutCandidature $depuis,
        StatutCandidature $vers,
        bool $attendu
    ): void {
        $this->assertSame($attendu, $depuis->peutDevenir($vers));
    }

    public static function transitionsAutorisees(): array
    {
        return [
            // EnAttente -> ...
            'en_attente -> en_cours'         => [StatutCandidature::EnAttente, StatutCandidature::EnCours,         true],
            'en_attente -> preselectionnee'  => [StatutCandidature::EnAttente, StatutCandidature::Preselectionnee, true],
            'en_attente -> refusee'          => [StatutCandidature::EnAttente, StatutCandidature::Refusee,         true],
            'en_attente -> acceptee'         => [StatutCandidature::EnAttente, StatutCandidature::Acceptee,        false],

            // EnCours -> ...
            'en_cours -> preselectionnee'    => [StatutCandidature::EnCours, StatutCandidature::Preselectionnee, true],
            'en_cours -> refusee'            => [StatutCandidature::EnCours, StatutCandidature::Refusee,         true],
            'en_cours -> acceptee'           => [StatutCandidature::EnCours, StatutCandidature::Acceptee,        false],
            'en_cours -> en_attente'         => [StatutCandidature::EnCours, StatutCandidature::EnAttente,       false],

            // Preselectionnee -> ...
            'preselectionnee -> acceptee'    => [StatutCandidature::Preselectionnee, StatutCandidature::Acceptee, true],
            'preselectionnee -> refusee'     => [StatutCandidature::Preselectionnee, StatutCandidature::Refusee,  true],
            'preselectionnee -> en_cours'    => [StatutCandidature::Preselectionnee, StatutCandidature::EnCours,  false],

            // Statuts definitifs
            'acceptee -> tout'               => [StatutCandidature::Acceptee, StatutCandidature::EnCours, false],
            'refusee -> tout'                => [StatutCandidature::Refusee,  StatutCandidature::EnCours, false],
        ];
    }

    // -----------------------------------------------------------------
    // Statuts definitifs
    // -----------------------------------------------------------------

    #[DataProvider('statutsDefinitifs')]
    public function test_est_definitif_detecte_les_statuts_finaux(
        StatutCandidature $statut,
        bool $attendu
    ): void {
        $this->assertSame($attendu, $statut->estDefinitif());
    }

    public static function statutsDefinitifs(): array
    {
        return [
            'en_attente'       => [StatutCandidature::EnAttente,       false],
            'en_cours'         => [StatutCandidature::EnCours,         false],
            'preselectionnee'  => [StatutCandidature::Preselectionnee, false],
            'acceptee'         => [StatutCandidature::Acceptee,        true],
            'refusee'          => [StatutCandidature::Refusee,         true],
        ];
    }

    // -----------------------------------------------------------------
    // Coherence globale du cycle
    // -----------------------------------------------------------------

    public function test_les_statuts_definitifs_n_ont_aucune_suivante(): void
    {
        $this->assertSame([], StatutCandidature::Acceptee->suivantes());
        $this->assertSame([], StatutCandidature::Refusee->suivantes());
    }

    public function test_aucun_statut_ne_transite_vers_lui_meme(): void
    {
        foreach (StatutCandidature::cases() as $statut) {
            $this->assertFalse(
                $statut->peutDevenir($statut),
                "Le statut {$statut->value} ne doit pas pouvoir transitionner vers lui-meme."
            );
        }
    }

    public function test_en_attente_est_le_seul_point_d_entree_possible(): void
    {
        // Aucun statut ne peut revenir vers EnAttente.
        foreach (StatutCandidature::cases() as $statut) {
            $this->assertFalse(
                $statut->peutDevenir(StatutCandidature::EnAttente),
                "Le statut {$statut->value} ne doit pas pouvoir revenir vers en_attente."
            );
        }
    }
}
