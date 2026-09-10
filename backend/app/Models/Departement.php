<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Departement extends Model
{
    use HasFactory;

    protected $table      = 'departements';
    protected $primaryKey = 'id_departement';

    protected $fillable = ['id_entreprise', 'nom', 'description'];

    protected function casts(): array
    {
        // La comparaison de propriété dans DepartementPolicy est stricte :
        // la clé étrangère doit être un entier quel que soit le pilote.
        return ['id_entreprise' => 'integer'];
    }

    /** RG9 — chaque département appartient à une et une seule entreprise. */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }
}
