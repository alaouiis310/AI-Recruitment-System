<?php

namespace App\Enums;

/** Conclusion de l'analyse automatique d'une candidature (RG42). */
enum Recommandation: string
{
    case Retenir   = 'retenir';
    case AExaminer = 'a_examiner';
    case Rejeter   = 'rejeter';

    public function libelle(): string
    {
        return match ($this) {
            self::Retenir   => 'À retenir',
            self::AExaminer => 'À examiner',
            self::Rejeter   => 'À rejeter',
        };
    }

    /** Seuils de décision. */
    public static function depuisScore(float $score): self
    {
        return match (true) {
            $score >= 70.0 => self::Retenir,
            $score >= 45.0 => self::AExaminer,
            default        => self::Rejeter,
        };
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
