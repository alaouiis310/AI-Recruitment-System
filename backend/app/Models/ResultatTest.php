<?php

namespace App\Models;

use App\Enums\StatutResultatTest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultatTest extends Model
{
    use HasFactory;

    protected $table = 'resultats_tests';

    protected $primaryKey = 'id_resultat';

    protected $fillable = [
        'date_passage',
        'score_obtenu',
        'statut',
        'commentaire',
        'id_candidature',
        'id_test',
    ];

    protected function casts(): array
    {
        return [
            'statut'         => StatutResultatTest::class,
            'date_passage'   => 'date',
            'score_obtenu'   => 'integer',
            'id_candidature' => 'integer',
            'id_test'        => 'integer',
        ];
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class, 'id_candidature');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(TestTechnique::class, 'id_test');
    }

    /** RG14 — résultats relevant des offres publiées par un recruteur donné. */
    public function scopeDuRecruteur(Builder $query, int $idRecruteur): Builder
    {
        return $query->whereHas(
            'candidature.offre',
            fn (Builder $q) => $q->where('id_recruteur', $idRecruteur),
        );
    }

    /** Score ramené sur 100, quel que soit le barème du test. */
    public function pourcentage(): ?float
    {
        if (! $this->statut->porteUnScore() || $this->score_obtenu === null) {
            return null;
        }

        $max = $this->test?->score_max ?? 100;

        return $max > 0 ? round(($this->score_obtenu / $max) * 100, 2) : null;
    }
}
