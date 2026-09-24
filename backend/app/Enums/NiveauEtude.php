<?php

namespace App\Enums;

/** Niveau d'études exigé par une offre. */
enum NiveauEtude: string
{
    case Bac      = 'bac';
    case Bac2     = 'bac_2';
    case Bac3     = 'bac_3';
    case Bac5     = 'bac_5';
    case Bac8     = 'bac_8';

    public function libelle(): string
    {
        return match ($this) {
            self::Bac  => 'Baccalauréat',
            self::Bac2 => 'Bac +2 (DUT, BTS)',
            self::Bac3 => 'Bac +3 (Licence)',
            self::Bac5 => 'Bac +5 (Master, ingénieur)',
            self::Bac8 => 'Bac +8 (Doctorat)',
        };
    }

    /** Nombre d'années après le baccalauréat. */
    public function annees(): int
    {
        return match ($this) {
            self::Bac  => 0,
            self::Bac2 => 2,
            self::Bac3 => 3,
            self::Bac5 => 5,
            self::Bac8 => 8,
        };
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
