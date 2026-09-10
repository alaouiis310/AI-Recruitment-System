<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidat extends Model
{
    use HasFactory;

    protected $table      = 'candidats';

    protected $primaryKey = 'id_candidat';

    protected $fillable = [
        'id_user',
        'telephone',
        'telephone2',
        'adresse',
        'date_naissance',
        'diplome',
        'cv_pdf',
        'photo',
        'github',
        'linkedin',
        'experience_totale',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance'    => 'date',
            'experience_totale' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * RG24/RG25/RG26 — compétences déclarées, via le pivot posseder.
     * Table d'association : aucun modèle dédié, on passe par le pivot.
     */
    public function competences(): BelongsToMany
    {
        return $this->belongsToMany(
            Competence::class,
            'posseder',
            'id_candidat',
            'id_competence',
        )->withPivot('niveau', 'annees_experience');
    }

    /** RG27 — un candidat peut déposer plusieurs candidatures. */
    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class, 'id_candidat');
    }

    /** RG22 — le candidat dispose-t-il d'un CV exploitable par l'analyse (RG37) ? */
    public function possedeUnCv(): bool
    {
        return $this->cv_pdf !== null;
    }
}
