<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Offre\CreerOffreRequest;
use App\Models\Departement;
use App\Models\Recruteur;
use Illuminate\Validation\Validator;

class CreerOffreAdminRequest extends CreerOffreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estAdministrateur() === true;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'id_recruteur' => ['required', 'integer', 'exists:recruteurs,id_recruteur'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->hasAny(['id_recruteur', 'id_departement'])) {
                return;
            }

            $recruteur = Recruteur::find($this->integer('id_recruteur'));
            $departement = Departement::find($this->integer('id_departement'));

            if ($recruteur && $departement && $recruteur->id_entreprise !== $departement->id_entreprise) {
                $validator->errors()->add(
                    'id_departement',
                    "Ce département n'appartient pas à l'entreprise du recruteur sélectionné.",
                );
            }
        }];
    }
}
