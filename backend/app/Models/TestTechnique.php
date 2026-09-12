<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestTechnique extends Model
{
    use HasFactory;

    protected $table = 'tests_techniques';

    protected $primaryKey = 'id_test';

    protected $fillable = ['titre', 'description', 'duree', 'score_max'];

    protected function casts(): array
    {
        return [
            'duree'     => 'integer',
            'score_max' => 'integer',
        ];
    }

    /**
     * RG45 — offres auxquelles ce test est rattaché, via le pivot proposer.
     * Table d'association : aucun modèle dédié.
     */
    public function offres(): BelongsToMany
    {
        return $this->belongsToMany(OffreEmploi::class, 'proposer', 'id_test', 'id_offre');
    }

    /** RG45 — passages de ce test par des candidats. */
    public function resultats(): HasMany
    {
        return $this->hasMany(ResultatTest::class, 'id_test');
    }
}
