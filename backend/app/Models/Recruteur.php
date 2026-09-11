<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recruteur extends Model
{
    use HasFactory;

    protected $table      = 'recruteurs';

    protected $primaryKey = 'id_recruteur';

    protected $fillable = ['id_user', 'id_entreprise', 'telephone', 'poste'];

    protected function casts(): array
    {
        // La comparaison de propriété dans les politiques d'accès est stricte :
        // la clé étrangère doit être un entier quel que soit le pilote.
        return ['id_entreprise' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /** RG7 — un recruteur appartient à exactement une entreprise. */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

    /** RG12 — un recruteur peut publier plusieurs offres. */
    public function offres(): HasMany
    {
        return $this->hasMany(OffreEmploi::class, 'id_recruteur');
    }
}
