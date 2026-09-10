<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entreprise extends Model
{
    use HasFactory;

    protected $table      = 'entreprises';
    protected $primaryKey = 'id_entreprise';

    protected $fillable = [
        'nom',
        'secteur',
        'adresse',
        'ville',
        'site_web',
        'description',
    ];

    /** RG6 — une entreprise emploie un ou plusieurs recruteurs. */
    public function recruteurs(): HasMany
    {
        return $this->hasMany(Recruteur::class, 'id_entreprise');
    }

    /** RG8 — une entreprise est composée d'un ou plusieurs départements. */
    public function departements(): HasMany
    {
        return $this->hasMany(Departement::class, 'id_entreprise');
    }
}
