<?php

namespace App\Services;

use App\Models\Candidat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Profil du candidat : documents et compétences déclarées (RG22 à RG26).
 */
class ProfilCandidatService
{
    /** Disque public : les fichiers sont servis via storage:link. */
    private const DISQUE = 'public';

    /**
     * RG22/RG23 — remplace le CV du candidat. L'ancien fichier est supprimé :
     * le candidat peut mettre son CV à jour à tout moment, sans accumuler de
     * fichiers orphelins sur le disque.
     */
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

    /**
     * RG24/RG26 — remplace l'intégralité des compétences déclarées.
     */
    public function synchroniserCompetences(Candidat $candidat, array $competences): Candidat
    {
        $candidat->competences()->sync($this->pivot($competences));

        return $candidat->fresh('competences');
    }

    /**
     * RG24/RG26 — ajoute ou met à jour une compétence sans toucher aux autres.
     */
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

    /**
     * RG26 — met le pivot posseder en forme attendue par sync() : la clé est
     * l'identifiant de la compétence, la valeur ses attributs.
     */
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
