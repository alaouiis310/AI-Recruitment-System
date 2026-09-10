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

    /** RG13 — chaque offre est publiée par un seul recruteur. */
    public function recruteur(): BelongsTo
    {
        return $this->belongsTo(Recruteur::class, 'id_recruteur');
    }

    /** RG11 — chaque offre relève d'un seul département. */
    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'id_departement');
    }

    /**
     * RG19/RG20/RG21 — compétences requises via le pivot requerir.
     * Table d'association : aucun modèle dédié, on passe par le pivot.
     */
    public function competences(): BelongsToMany
    {
        return $this->belongsToMany(
            Competence::class,
            'requerir',
            'id_offre',
            'id_competence',
        )->withPivot('niveau_requis', 'importance');
    }

    // Les candidatures reçues (RG30) seront exposées ici avec le module 5.

    /** RG17/RG18 — une offre est visible des candidats si elle est ouverte et non expirée. */
    public function scopePubliable(Builder $query): Builder
    {
        return $query->where('statut', StatutOffre::Ouverte)
            ->where(function (Builder $q) {
                $q->whereNull('date_expiration')
                    ->orWhereDate('date_expiration', '>=', now()->toDateString());
            });
    }

    /** RG17 — l'offre a dépassé sa date d'expiration. */
    public function estExpiree(): bool
    {
        return $this->date_expiration !== null
            && $this->date_expiration->isBefore(now()->startOfDay());
    }

    /** RG18/RG27 — l'offre accepte-t-elle encore des candidatures ? */
    public function accepteCandidatures(): bool
    {
        return $this->statut->accepteCandidatures() && ! $this->estExpiree();
    }
}
