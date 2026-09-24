<?php

namespace App\Enums;

/** Cycle de vie d'une candidature (RG32). */
enum StatutCandidature: string
{
    case EnAttente       = 'en_attente';
    case EnCours         = 'en_cours';
    case Preselectionnee = 'preselectionnee';
    case Acceptee        = 'acceptee';
    case Refusee         = 'refusee';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente       => 'En attente',
            self::EnCours         => "En cours d'examen",
            self::Preselectionnee => 'Présélectionnée',
            self::Acceptee        => 'Acceptée',
            self::Refusee         => 'Refusée',
        };
    }

    /**
     * Statuts atteignables depuis celui-ci.
     *
     * @return array<int, self>
     */
    public function suivantes(): array
    {
        return match ($this) {
            self::EnAttente       => [self::EnCours, self::Preselectionnee, self::Refusee],
            self::EnCours         => [self::Preselectionnee, self::Refusee],
            self::Preselectionnee => [self::Acceptee, self::Refusee],
            self::Acceptee, self::Refusee => [],
        };
    }

    /** La transition vers $cible est-elle autorisée ? */
    public function peutDevenir(self $cible): bool
    {
        return in_array($cible, $this->suivantes(), true);
    }

    /** Statut définitif : plus aucune transition possible. */
    public function estDefinitif(): bool
    {
        return $this->suivantes() === [];
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
