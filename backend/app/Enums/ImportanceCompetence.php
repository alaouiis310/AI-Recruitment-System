<?php

namespace App\Enums;

/** Importance d'une compétence dans une offre (RG21). */
enum ImportanceCompetence: string
{
    case Essentielle = 'essentielle';
    case Importante  = 'importante';
    case Souhaitee   = 'souhaitee';

    public function libelle(): string
    {
        return match ($this) {
            self::Essentielle => 'Essentielle',
            self::Importante  => 'Importante',
            self::Souhaitee   => 'Souhaitée',
        };
    }

    /** Poids utilisé par ScoringService (module 6). */
    public function poids(): float
    {
        return match ($this) {
            self::Essentielle => 3.0,
            self::Importante  => 2.0,
            self::Souhaitee   => 1.0,
        };
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
