<?php

namespace App\Enums;

enum TypeContrat: string
{
    case Cdi         = 'cdi';
    case Cdd         = 'cdd';
    case Stage       = 'stage';
    case Alternance  = 'alternance';
    case Freelance   = 'freelance';
    case Interim     = 'interim';

    public function libelle(): string
    {
        return match ($this) {
            self::Cdi        => 'CDI',
            self::Cdd        => 'CDD',
            self::Stage      => 'Stage',
            self::Alternance => 'Alternance',
            self::Freelance  => 'Freelance',
            self::Interim    => 'Intérim',
        };
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
