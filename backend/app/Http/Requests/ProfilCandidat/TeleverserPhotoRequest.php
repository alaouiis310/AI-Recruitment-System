<?php

namespace App\Http\Requests\ProfilCandidat;

use Illuminate\Foundation\Http\FormRequest;

class TeleverserPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estCandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Aucun fichier reçu.',
            'photo.image'    => 'Le fichier doit être une image.',
            'photo.mimes'    => 'La photo doit être au format JPEG, PNG ou WebP.',
            'photo.max'      => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }
}
