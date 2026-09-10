<?php

namespace App\Enums;

enum CategorieCompetence: string
{
    case Langage     = 'langage';
    case Framework   = 'framework';
    case Outil       = 'outil';
    case BaseDonnees = 'base_de_donnees';
    case Langue      = 'langue';
    case SavoirEtre  = 'savoir_etre';

    public function libelle(): string
    {
        return match ($this) {
            self::Langage     => 'Langage de programmation',
            self::Framework   => 'Framework',
            self::Outil       => 'Outil',
            self::BaseDonnees => 'Base de données',
            self::Langue      => 'Langue',
            self::SavoirEtre  => 'Savoir-être',
        };
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
