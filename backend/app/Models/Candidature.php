<?php

namespace App\Models;

use App\Enums\StatutCandidature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** Une candidature appartient à un seul candidat (RG28). */
    public function candidat(): BelongsTo
    {
        return $this->belongsTo(Candidat::class, 'id_candidat');
    }

    /** Une candidature concerne une seule offre (RG29). */
    public function offre(): BelongsTo
    {
        return $this->belongsTo(OffreEmploi::class, 'id_offre');
    }

    /** Une candidature ne reçoit qu'une seule analyse (RG37/RG38). */
    public function analyse(): HasOne
    {
        return $this->hasOne(AnalyseIa::class, 'id_candidature');
    }

    /** Tests techniques envoyés au candidat pour cette candidature (RG45). */
    public function resultatsTests(): HasMany
    {
        return $this->hasMany(ResultatTest::class, 'id_candidature');
    }

    /** Une candidature peut compter zéro, un ou plusieurs entretiens (RG34). */
    public function entretiens(): HasMany
    {
        return $this->hasMany(Entretien::class, 'id_candidature');
    }

    /** Restreint aux candidatures portant sur les offres publiées par un recruteur donné (RG14). */
    public function scopeDuRecruteur(Builder $query, int $idRecruteur): Builder
    {
        return $query->whereHas('offre', fn (Builder $q) => $q->where('id_recruteur', $idRecruteur));
    }

    /** Classement par score décroissant (RG43). */
    public function scopeClasseeParScore(Builder $query): Builder
    {
        return $query->orderByRaw('score_final IS NULL')
            ->orderByDesc('score_final')
            ->orderByDesc('date_candidature');
    }
}
