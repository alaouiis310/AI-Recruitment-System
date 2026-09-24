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
        // La comparaison de propriété dans les politiques d'accès est stricte.
        return ['id_entreprise' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /** Un recruteur appartient à exactement une entreprise (RG7). */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

    /** Un recruteur peut publier plusieurs offres (RG12). */
    public function offres(): HasMany
    {
        return $this->hasMany(OffreEmploi::class, 'id_recruteur');
    }
}
