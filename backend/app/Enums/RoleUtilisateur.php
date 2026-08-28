<?php

namespace App\Enums;

enum RoleUtilisateur: string
{
    case Administrateur = 'administrateur';
    case Recruteur      = 'recruteur';
    case Candidat       = 'candidat';

    public function libelle(): string
    {
        return match ($this) {
            self::Administrateur => 'Administrateur',
            self::Recruteur      => 'Recruteur',
            self::Candidat       => 'Candidat',
        };
    }

    public static function inscriptionPublique(): array
    {
        return [self::Candidat->value, self::Recruteur->value];
    }
}
