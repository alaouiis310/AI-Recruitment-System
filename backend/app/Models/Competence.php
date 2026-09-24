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

    /** Offres exigeant cette compétence, via le pivot requerir (RG19/RG20). */
    public function offres(): BelongsToMany
    {
        return $this->belongsToMany(
            OffreEmploi::class,
            'requerir',
            'id_competence',
            'id_offre',
        )->withPivot('niveau_requis', 'importance');
    }

    /** Candidats déclarant cette compétence, via posseder (RG24/RG25). */
    public function candidats(): BelongsToMany
    {
        return $this->belongsToMany(
            Candidat::class,
            'posseder',
            'id_competence',
            'id_candidat',
        )->withPivot('niveau', 'annees_experience');
    }
}
