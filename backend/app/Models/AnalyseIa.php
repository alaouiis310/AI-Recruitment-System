<?php

namespace App\Models;

use App\Enums\Recommandation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyseIa extends Model
{
    use HasFactory;

    protected $table = 'analyses_ia';

    protected $primaryKey = 'id_analyse';

    protected $fillable = [
        'score_matching',
        'score_competence',
        'score_experience',
        'score_diplome',
        'competences_manquantes',
        'resume_cv',
        'recommandation',
        'date_analyse',
        'id_candidature',
    ];

    protected function casts(): array
    {
        return [
            'recommandation'         => Recommandation::class,
            'competences_manquantes' => 'array',
            'date_analyse'           => 'date',
            'score_matching'         => 'decimal:2',
            'score_competence'       => 'decimal:2',
            'score_experience'       => 'decimal:2',
            'score_diplome'          => 'decimal:2',
            'id_candidature'         => 'integer',
        ];
    }

    /** Une analyse porte sur une seule candidature (RG37/RG38). */
    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class, 'id_candidature');
    }
}
