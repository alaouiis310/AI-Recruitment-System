<?php

namespace App\Models;

use App\Enums\CategorieCompetence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competence extends Model
{
    use HasFactory;

    protected $table = 'competences';

    protected $primaryKey = 'id_competence';

    protected $fillable = ['nom', 'categorie', 'description'];

    protected function casts(): array
    {
        return ['categorie' => CategorieCompetence::class];
    }

    /**
     * RG19/RG20 — offres exigeant cette compétence, via le pivot requerir.
     * Table d'association : aucun modèle dédié, on passe par le pivot.
     */
    public function offres(): BelongsToMany
    {
        return $this->belongsToMany(
            OffreEmploi::class,
            'requerir',
            'id_competence',
            'id_offre',
        )->withPivot('niveau_requis', 'importance');
    }

    // RG24/RG25 — la relation vers les candidats (pivot posseder) sera
    // ajoutée avec le module 4.
}
