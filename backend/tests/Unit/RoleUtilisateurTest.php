<?php

namespace Tests\Unit;

use App\Enums\RoleUtilisateur;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs de l'énumération RoleUtilisateur.
 *
 * Vérifie la stabilité des rôles et surtout que l'inscription publique
 * n'expose jamais le rôle Administrateur (règle de sécurité implicite).
 */
class RoleUtilisateurTest extends TestCase
{
    // -----------------------------------------------------------------
    // Structure de l'énumération
    // -----------------------------------------------------------------

    public function test_l_enumeration_expose_trois_roles(): void
    {
        $this->assertCount(3, RoleUtilisateur::cases());
    }

    public function test_les_valeurs_string_sont_stables(): void
    {
        $this->assertSame('administrateur', RoleUtilisateur::Administrateur->value);
        $this->assertSame('recruteur', RoleUtilisateur::Recruteur->value);
        $this->assertSame('candidat', RoleUtilisateur::Candidat->value);
    }

    // -----------------------------------------------------------------
    // Libellés affichés à l'utilisateur
    // -----------------------------------------------------------------

    #[DataProvider('libellesAttendus')]
    public function test_les_libelles_sont_corrects(RoleUtilisateur $role, string $attendu): void
    {
        $this->assertSame($attendu, $role->libelle());
    }

    public static function libellesAttendus(): array
    {
        return [
            'administrateur' => [RoleUtilisateur::Administrateur, 'Administrateur'],
            'recruteur'      => [RoleUtilisateur::Recruteur,      'Recruteur'],
            'candidat'       => [RoleUtilisateur::Candidat,       'Candidat'],
        ];
    }

    // -----------------------------------------------------------------
    // Inscription publique — règle de sécurité
    // -----------------------------------------------------------------

    public function test_l_inscription_publique_autorise_deux_roles(): void
    {
        $this->assertCount(2, RoleUtilisateur::inscriptionPublique());
    }

    public function test_l_inscription_publique_autorise_candidat_et_recruteur(): void
    {
        $this->assertContains('candidat', RoleUtilisateur::inscriptionPublique());
        $this->assertContains('recruteur', RoleUtilisateur::inscriptionPublique());
    }

    /**
     * Regle de securite : un visiteur ne doit JAMAIS pouvoir s'inscrire
     * directement en tant qu'administrateur.
     */
    public function test_l_inscription_publique_exclut_administrateur(): void
    {
        $this->assertNotContains(
            'administrateur',
            RoleUtilisateur::inscriptionPublique()
        );
    }

    public function test_l_inscription_publique_ne_renvoie_que_des_valeurs_valides(): void
    {
        $valeursValides = array_column(RoleUtilisateur::cases(), 'value');

        foreach (RoleUtilisateur::inscriptionPublique() as $role) {
            $this->assertContains($role, $valeursValides);
        }
    }
}
