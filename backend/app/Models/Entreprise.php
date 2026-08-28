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

    public function recruteurs(): HasMany
    {
        return $this->hasMany(Recruteur::class, 'id_entreprise');
    }
}
