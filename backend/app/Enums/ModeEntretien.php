<?php

namespace App\Enums;

/** Modalité de déroulement de l'entretien (RG36). */
enum ModeEntretien: string
{
    case Presentiel = 'presentiel';
    case Visio      = 'visio';
    case Telephone  = 'telephone';

    public function libelle(): string
    {
        return match ($this) {
            self::Presentiel => 'Présentiel',
            self::Visio      => 'Visioconférence',
            self::Telephone  => 'Téléphone',
        };
    }

    /** Seul un entretien à distance porte un lien de connexion. */
    public function exigeUnLien(): bool
    {
        return $this === self::Visio;
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
