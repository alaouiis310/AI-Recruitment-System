<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recruteur extends Model
{
    use HasFactory;

    protected $table      = 'recruteurs';
    protected $primaryKey = 'id_recruteur';

    protected $fillable = ['id_user', 'id_entreprise', 'telephone', 'poste'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }
}
