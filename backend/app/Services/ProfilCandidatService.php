<?php

namespace App\Services;

use App\Models\Candidat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Profil du candidat : documents et compétences déclarées (RG22 à RG26). */
class ProfilCandidatService
{
    /** Disque public : les fichiers sont servis via storage:link. */
    private const DISQUE = 'public';

    /** Remplace le CV du candidat (RG22/RG23). */
    public function remplacerCv(Candidat $candidat, UploadedFile $fichier): Candidat
    {
        return $this->remplacerFichier($candidat, 'cv_pdf', $fichier, 'cv');
    }

    /** Remplace la photo de profil du candidat. */
    public function remplacerPhoto(Candidat $candidat, UploadedFile $fichier): Candidat
    {
        return $this->remplacerFichier($candidat, 'photo', $fichier, 'photos');
    }

    /** Supprime le CV et le fichier associé. */
    public function supprimerCv(Candidat $candidat): Candidat
    {
        return $this->supprimerFichier($candidat, 'cv_pdf');
    }

    /** Supprime la photo et le fichier associé. */
    public function supprimerPhoto(Candidat $candidat): Candidat
    {
        return $this->supprimerFichier($candidat, 'photo');
    }

    /** Remplace l'intégralité des compétences déclarées (RG24/RG26). */
    public function synchroniserCompetences(Candidat $candidat, array $competences): Candidat
    {
        $candidat->competences()->sync($this->pivot($competences));

        return $candidat->fresh('competences');
    }

    /** Ajoute ou met à jour une compétence sans toucher aux autres (RG24/RG26). */
    public function declarerCompetence(Candidat $candidat, array $competence): Candidat
    {
        $candidat->competences()->syncWithoutDetaching($this->pivot([$competence]));

        return $candidat->fresh('competences');
    }

    /** Retire une compétence déclarée. */
    public function retirerCompetence(Candidat $candidat, int $idCompetence): Candidat
    {
        $candidat->competences()->detach($idCompetence);

        return $candidat->fresh('competences');
    }

    /** Met le pivot posseder en forme attendue par sync() (RG26). */
    private function pivot(array $competences): array
    {
        return collect($competences)
            ->keyBy('id_competence')
            ->map(fn (array $c) => [
                'niveau'            => $c['niveau'],
                'annees_experience' => $c['annees_experience'] ?? 0,
            ])
            ->all();
    }

    /** Stocke le nouveau fichier puis efface le précédent, une fois l'écriture réussie. */
    private function remplacerFichier(Candidat $candidat, string $colonne, UploadedFile $fichier, string $dossier): Candidat
    {
        $ancien = $candidat->{$colonne};

        $chemin = $fichier->store($dossier, self::DISQUE);

        $candidat->update([$colonne => $chemin]);

        if ($ancien !== null && $ancien !== $chemin) {
            Storage::disk(self::DISQUE)->delete($ancien);
        }

        return $candidat->fresh();
    }

    private function supprimerFichier(Candidat $candidat, string $colonne): Candidat
    {
        $chemin = $candidat->{$colonne};

        if ($chemin !== null) {
            Storage::disk(self::DISQUE)->delete($chemin);
            $candidat->update([$colonne => null]);
        }

        return $candidat->fresh();
    }
}
