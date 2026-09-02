<?php

namespace App\Enums;

enum EtatCompte: string
{
    case Actif      = 'actif';
    case Suspendu   = 'suspendu';
    case Desactive  = 'desactive';

    public function libelle(): string
    {
        return match ($this) {
            self::Actif     => 'Actif',
            self::Suspendu  => 'Suspendu',
            self::Desactive => 'Désactivé',
        };
    }

    public function peutSeConnecter(): bool
    {
        return $this === self::Actif;
    }
}
