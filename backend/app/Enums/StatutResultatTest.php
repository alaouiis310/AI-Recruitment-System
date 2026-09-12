<?php

namespace App\Enums;

/** RG45 — état du passage d'un test technique par un candidat. */
enum StatutResultatTest: string
{
    case Envoye   = 'envoye';
    case EnCours  = 'en_cours';
    case Termine  = 'termine';
    case Expire   = 'expire';

    public function libelle(): string
    {
        return match ($this) {
            self::Envoye  => 'Envoyé',
            self::EnCours => 'En cours',
            self::Termine => 'Terminé',
            self::Expire  => 'Expiré',
        };
    }

    /** Seul un test terminé porte un score exploitable. */
    public function porteUnScore(): bool
    {
        return $this === self::Termine;
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
