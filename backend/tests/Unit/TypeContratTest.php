<?php

namespace Tests\Unit;

use App\Enums\TypeContrat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs de l'enumeration TypeContrat.
 *
 * Verifie que les 6 types de contrat sont bien definis, que leurs libelles
 * affiches sont corrects (notamment Interim avec accent) et que les valeurs
 * utilisees en base de donnees restent stables.
 */
class TypeContratTest extends TestCase
{
    // -----------------------------------------------------------------
    // Structure de l'enumeration
    // -----------------------------------------------------------------

    public function test_l_enumeration_expose_six_types_de_contrat(): void
    {
        $this->assertCount(6, TypeContrat::cases());
    }

    public function test_les_valeurs_string_sont_stables(): void
    {
        $this->assertSame('cdi',        TypeContrat::Cdi->value);
        $this->assertSame('cdd',        TypeContrat::Cdd->value);
        $this->assertSame('stage',      TypeContrat::Stage->value);
        $this->assertSame('alternance', TypeContrat::Alternance->value);
        $this->assertSame('freelance',  TypeContrat::Freelance->value);
        $this->assertSame('interim',    TypeContrat::Interim->value);
    }

    public function test_la_methode_valeurs_renvoie_les_six_valeurs(): void
    {
        $this->assertSame(
            ['cdi', 'cdd', 'stage', 'alternance', 'freelance', 'interim'],
            TypeContrat::valeurs()
        );
    }

    // -----------------------------------------------------------------
    // Libelles affiches a l'utilisateur
    // -----------------------------------------------------------------

    #[DataProvider('libellesAttendus')]
    public function test_les_libelles_sont_corrects(TypeContrat $type, string $attendu): void
    {
        $this->assertSame($attendu, $type->libelle());
    }

    public static function libellesAttendus(): array
    {
        return [
            'cdi'        => [TypeContrat::Cdi,        'CDI'],
            'cdd'        => [TypeContrat::Cdd,        'CDD'],
            'stage'      => [TypeContrat::Stage,      'Stage'],
            'alternance' => [TypeContrat::Alternance, 'Alternance'],
            'freelance'  => [TypeContrat::Freelance,  'Freelance'],
            'interim'    => [TypeContrat::Interim,    'Intérim'],
        ];
    }

    // -----------------------------------------------------------------
    // Coherence globale
    // -----------------------------------------------------------------

    public function test_aucune_valeur_string_n_est_en_double(): void
    {
        $valeurs = TypeContrat::valeurs();
        $uniques = array_unique($valeurs);

        $this->assertSame(count($valeurs), count($uniques));
    }

    public function test_aucun_libelle_n_est_en_double(): void
    {
        $libelles = array_map(
            fn (TypeContrat $t) => $t->libelle(),
            TypeContrat::cases()
        );
        $uniques = array_unique($libelles);

        $this->assertSame(count($libelles), count($uniques));
    }

    public function test_les_valeurs_sont_toutes_en_minuscules(): void
    {
        foreach (TypeContrat::valeurs() as $valeur) {
            $this->assertSame(
                strtolower($valeur),
                $valeur,
                "La valeur '{$valeur}' doit etre en minuscules."
            );
        }
    }

    public function test_les_valeurs_ne_contiennent_pas_d_accents(): void
    {
        // Les valeurs en base de donnees ne doivent contenir que [a-z].
        foreach (TypeContrat::valeurs() as $valeur) {
            $this->assertMatchesRegularExpression(
                '/^[a-z]+$/',
                $valeur,
                "La valeur '{$valeur}' doit contenir uniquement des lettres minuscules."
            );
        }
    }
}