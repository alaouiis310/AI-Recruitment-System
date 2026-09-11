<?php

namespace App\Models;

use App\Enums\StatutCandidature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Candidature extends Model
{
    use HasFactory;

    protected $table = 'candidatures';

    protected $primaryKey = 'id_candidature';

    protected $fillable = [
        'date_candidature',
        'lettre_motivation',
        'statut',
        'score_final',
        'date_decision',
        'commentaire_recruteur',
        'id_candidat',
        'id_offre',
    ];

    protected function casts(): array
    {
        return [
            'statut'           => StatutCandidature::class,
            'date_candidature' => 'date',
            'date_decision'    => 'date',
            'score_final'      => 'decimal:2',
            'id_candidat'      => 'integer',
            'id_offre'         => 'integer',
        ];
    }

    /** RG28 — une candidature appartient à un seul candidat. */
    public function candidat(): BelongsTo
    {
        return $this->belongsTo(Candidat::class, 'id_candidat');
    }

    /** RG29 — une candidature concerne une seule offre. */
    public function offre(): BelongsTo
    {
        return $this->belongsTo(OffreEmploi::class, 'id_offre');
    }

    /** RG37/RG38 — une candidature ne reçoit qu'une seule analyse. */
    public function analyse(): HasOne
    {
        return $this->hasOne(AnalyseIa::class, 'id_candidature');
    }

    // Les entretiens liés (RG34) seront exposés ici lorsque le module
    // correspondant sera développé.

    /**
     * RG14 — restreint aux candidatures portant sur les offres publiées par
     * un recruteur donné. La restriction est appliquée par la base, jamais en
     * filtrant une collection déjà chargée.
     */
    public function scopeDuRecruteur(Builder $query, int $idRecruteur): Builder
    {
        return $query->whereHas('offre', fn (Builder $q) => $q->where('id_recruteur', $idRecruteur));
    }

    /**
     * RG43 — classement par score décroissant. Les candidatures non encore
     * analysées passent en dernier, sans disparaître de la liste.
     */
    public function scopeClasseeParScore(Builder $query): Builder
    {
        return $query->orderByRaw('score_final IS NULL')
            ->orderByDesc('score_final')
            ->orderByDesc('date_candidature');
    }
}
