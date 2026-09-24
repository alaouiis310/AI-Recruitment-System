<?php

namespace App\Models;

use App\Enums\NiveauEtude;
use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffreEmploi extends Model
{
    use HasFactory;

    protected $table = 'offres_emploi';

    protected $primaryKey = 'id_offre';

    protected $fillable = [
        'titre',
        'description',
        'type_contrat',
        'localisation',
        'salaire',
        'experience_min',
        'niveau_etude',
        'date_publication',
        'date_expiration',
        'statut',
        'id_recruteur',
        'id_departement',
    ];

    protected function casts(): array
    {
        return [
            'type_contrat'     => TypeContrat::class,
            'statut'           => StatutOffre::class,
            'niveau_etude'     => NiveauEtude::class,
            'date_publication' => 'date',
            'date_expiration'  => 'date',
            'salaire'          => 'decimal:2',
            'experience_min'   => 'decimal:1',
            'id_recruteur'     => 'integer',
            'id_departement'   => 'integer',
        ];
    }

    /** Chaque offre est publiée par un seul recruteur (RG13). */
    public function recruteur(): BelongsTo
    {
        return $this->belongsTo(Recruteur::class, 'id_recruteur');
    }

    /** Chaque offre relève d'un seul département (RG11). */
    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'id_departement');
    }

    /** Compétences requises via le pivot requerir (RG19/RG20/RG21). */
    public function competences(): BelongsToMany
    {
        return $this->belongsToMany(
            Competence::class,
            'requerir',
            'id_offre',
            'id_competence',
        )->withPivot('niveau_requis', 'importance');
    }

    /** Une offre peut recevoir plusieurs candidatures (RG30). */
    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class, 'id_offre');
    }

    /** Tests techniques rattachés à l'offre, via le pivot proposer (RG45). */
    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(TestTechnique::class, 'proposer', 'id_offre', 'id_test');
    }

    /** Une offre est visible des candidats si elle est ouverte et non expirée (RG17/RG18). */
    public function scopePubliable(Builder $query): Builder
    {
        return $query->where('statut', StatutOffre::Ouverte)
            ->where(function (Builder $q) {
                $q->whereNull('date_expiration')
                    ->orWhereDate('date_expiration', '>=', now()->toDateString());
            });
    }

    /** L'offre a dépassé sa date d'expiration (RG17). */
    public function estExpiree(): bool
    {
        return $this->date_expiration !== null
            && $this->date_expiration->isBefore(now()->startOfDay());
    }

    /** L'offre accepte-t-elle encore des candidatures (RG18/RG27) ? */
    public function accepteCandidatures(): bool
    {
        return $this->statut->accepteCandidatures() && ! $this->estExpiree();
    }
}
