<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidat extends Model
{
    use HasFactory;

    protected $table      = 'candidats';
    protected $primaryKey = 'id_candidat';

    protected $fillable = [
        'id_user',
        'telephone',
        'telephone2',
        'adresse',
        'date_naissance',
        'diplome',
        'cv_pdf',
        'photo',
        'github',
        'linkedin',
        'experience_totale',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance'    => 'date',
            'experience_totale' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
