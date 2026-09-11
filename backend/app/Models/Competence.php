<?php

namespace App\Models;

use App\Enums\CategorieCompetence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // RG19/RG20 — la relation vers les offres (pivot requerir) sera ajoutée
    // avec le module 3, RG24/RG25 celle vers les candidats (pivot posseder)
    // avec le module 4.
}
