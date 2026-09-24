<?php

namespace App\Enums;

/** Issue d'un entretien (RG36). */
enum ResultatEntretien: string
{
    case EnAttente   = 'en_attente';
    case Favorable   = 'favorable';
    case Defavorable = 'defavorable';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente   => 'En attente',
            self::Favorable   => 'Favorable',
            self::Defavorable => 'Défavorable',
        };
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
