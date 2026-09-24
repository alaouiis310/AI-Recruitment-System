<?php

namespace App\Models;

use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entretien extends Model
{
    use HasFactory;

    protected $table = 'entretiens';

    protected $primaryKey = 'id_entretien';

    protected $fillable = [
        'date',
        'heure',
        'mode',
        'lien_si_online',
        'commentaire',
        'resultat',
        'id_candidature',
    ];

    protected function casts(): array
    {
        return [
            'mode'           => ModeEntretien::class,
            'resultat'       => ResultatEntretien::class,
            'date'           => 'date',
            'id_candidature' => 'integer',
        ];
    }

    /** Un entretien porte sur une seule candidature (RG35). */
    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class, 'id_candidature');
    }

    /** Restreint aux entretiens des candidatures portant sur les offres publiées par un recruteur donné (RG14). */
    public function scopeDuRecruteur(Builder $query, int $idRecruteur): Builder
    {
        return $query->whereHas(
            'candidature.offre',
            fn (Builder $q) => $q->where('id_recruteur', $idRecruteur),
        );
    }

    /** Entretien encore à venir. */
    public function estAVenir(): bool
    {
        return $this->date->isFuture()
            || ($this->date->isToday() && $this->resultat === ResultatEntretien::EnAttente);
    }
}
