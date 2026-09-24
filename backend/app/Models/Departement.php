<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    use HasFactory;

    protected $table      = 'departements';

    protected $primaryKey = 'id_departement';

    protected $fillable = ['id_entreprise', 'nom', 'description'];

    protected function casts(): array
    {
        // La comparaison de propriété dans DepartementPolicy est stricte.
        return ['id_entreprise' => 'integer'];
    }

    /** Chaque département appartient à une et une seule entreprise (RG9). */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

    /** Un département gère plusieurs offres (RG10). */
    public function offres(): HasMany
    {
        return $this->hasMany(OffreEmploi::class, 'id_departement');
    }
}
