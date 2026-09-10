<?php

namespace App\Enums;

/** RG18 — une offre est ouverte, fermée ou suspendue. */
enum StatutOffre: string
{
    case Ouverte   = 'ouverte';
    case Fermee    = 'fermee';
    case Suspendue = 'suspendue';

    public function libelle(): string
    {
        return match ($this) {
            self::Ouverte   => 'Ouverte',
            self::Fermee    => 'Fermée',
            self::Suspendue => 'Suspendue',
        };
    }

    /** Seule une offre ouverte accepte des candidatures (RG27, RG30). */
    public function accepteCandidatures(): bool
    {
        return $this === self::Ouverte;
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
