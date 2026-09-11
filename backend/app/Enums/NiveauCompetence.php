<?php

namespace App\Enums;

/**
 * RG21/RG26 — niveau de maîtrise, exigé par une offre (requerir) ou déclaré
 * par un candidat (posseder). L'ordre est significatif : il sert au calcul de
 * l'écart de niveau dans le score de compatibilité (RG40).
 */
enum NiveauCompetence: string
{
    case Debutant      = 'debutant';
    case Intermediaire = 'intermediaire';
    case Avance        = 'avance';
    case Expert        = 'expert';

    public function libelle(): string
    {
        return match ($this) {
            self::Debutant      => 'Débutant',
            self::Intermediaire => 'Intermédiaire',
            self::Avance        => 'Avancé',
            self::Expert        => 'Expert',
        };
    }

    /** Rang ordinal, de 1 (débutant) à 4 (expert). */
    public function rang(): int
    {
        return match ($this) {
            self::Debutant      => 1,
            self::Intermediaire => 2,
            self::Avance        => 3,
            self::Expert        => 4,
        };
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
