<?php

namespace App\Models;

use App\Enums\EtatCompte;
use App\Enums\RoleUtilisateur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'prenom',
        'email',
        'password',
        'role',
        'etat_compte',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => RoleUtilisateur::class,
            'etat_compte'       => EtatCompte::class,
        ];
    }

    public function candidat(): HasOne
    {
        return $this->hasOne(Candidat::class, 'id_user');
    }

    public function recruteur(): HasOne
    {
        return $this->hasOne(Recruteur::class, 'id_user');
    }

    public function estCandidat(): bool
    {
        return $this->role === RoleUtilisateur::Candidat;
    }

    public function estRecruteur(): bool
    {
        return $this->role === RoleUtilisateur::Recruteur;
    }

    public function estAdministrateur(): bool
    {
        return $this->role === RoleUtilisateur::Administrateur;
    }

    public function compteActif(): bool
    {
        return $this->etat_compte->peutSeConnecter();
    }

    public function nomComplet(): string
    {
        return trim("{$this->prenom} {$this->name}");
    }

    public function profil(): ?object
    {
        return match (true) {
            $this->estCandidat()  => $this->candidat,
            $this->estRecruteur() => $this->recruteur,
            default               => null,
        };
    }
}
