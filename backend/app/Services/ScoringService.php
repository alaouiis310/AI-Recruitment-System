<?php

namespace App\Services;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Competence;
use App\Models\OffreEmploi;

/** Calcul du score de compatibilité entre un candidat et une offre (RG40). */
class ScoringService
{
    /** Pondération des trois volets. */
    private const POIDS_COMPETENCE = 0.60;

    private const POIDS_EXPERIENCE = 0.25;

    private const POIDS_DIPLOME = 0.15;

    /**
     * Calcule les quatre scores et la liste des compétences manquantes.
     *
     * @return array<string, mixed>
     */
    public function evaluer(Candidature $candidature): array
    {
        $candidat = $candidature->candidat->loadMissing('competences');
        $offre    = $candidature->offre->loadMissing('competences');

        [$scoreCompetence, $manquantes] = $this->voletCompetences($candidat, $offre);

        $scoreExperience = $this->voletExperience($candidat, $offre);
        $scoreDiplome    = $this->voletDiplome($candidat, $offre);

        $global = $scoreCompetence * self::POIDS_COMPETENCE
            + $scoreExperience * self::POIDS_EXPERIENCE
            + $scoreDiplome * self::POIDS_DIPLOME;

        return [
            'score_matching'         => $this->borner($global),
            'score_competence'       => $this->borner($scoreCompetence),
            'score_experience'       => $this->borner($scoreExperience),
            'score_diplome'          => $this->borner($scoreDiplome),
            'competences_manquantes' => $manquantes,
        ];
    }

    /**
     * Confronte les compétences déclarées par le candidat (pivot posseder) à celles exigées par
     * l'offre (pivot requerir) (RG19/RG21/RG24).
     *
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     */
    private function voletCompetences(Candidat $candidat, OffreEmploi $offre): array
    {
        $exigees = $offre->competences;

        // Une offre sans exigence ne départage pas les candidats : le volet est neutre plutôt que nul.
        if ($exigees->isEmpty()) {
            return [100.0, []];
        }

        $declarees = $candidat->competences->keyBy('id_competence');

        $pointsObtenus  = 0.0;
        $pointsPossible = 0.0;
        $manquantes     = [];

        foreach ($exigees as $competence) {
            $importance = $competence->pivot->importance;
            $poids      = ImportanceCompetence::from($importance)->poids();
            $requis     = NiveauCompetence::from($competence->pivot->niveau_requis);

            $pointsPossible += $poids;

            $declaree = $declarees->get($competence->id_competence);

            if ($declaree === null) {
                $manquantes[] = $this->manquante($competence, $requis, null, $importance);

                continue;
            }

            $possede = NiveauCompetence::from($declaree->pivot->niveau);
            $ratio   = $this->ratioNiveau($possede, $requis);

            $pointsObtenus += $poids * $ratio;

            if ($ratio < 1.0) {
                $manquantes[] = $this->manquante($competence, $requis, $possede, $importance);
            }
        }

        return [($pointsObtenus / $pointsPossible) * 100, $manquantes];
    }

    /** Description d'une compétence absente ou insuffisante (RG41). */
    private function manquante(Competence $competence, NiveauCompetence $requis, ?NiveauCompetence $possede, string $importance): array
    {
        return [
            'id_competence' => $competence->id_competence,
            'nom'           => $competence->nom,
            'niveau_requis' => $requis->value,
            'niveau_actuel' => $possede?->value,
            'importance'    => $importance,
        ];
    }

    /** Fraction des points accordée selon l'écart de niveau. */
    private function ratioNiveau(NiveauCompetence $possede, NiveauCompetence $requis): float
    {
        $ecart = $requis->rang() - $possede->rang();

        if ($ecart <= 0) {
            return 1.0;
        }

        return max(0.0, 1.0 - 0.25 * $ecart);
    }

    /** Compare l'expérience totale du candidat à celle exigée par l'offre. */
    private function voletExperience(Candidat $candidat, OffreEmploi $offre): float
    {
        $exigee = (float) $offre->experience_min;

        if ($exigee <= 0.0) {
            return 100.0;
        }

        return min(100.0, ((float) $candidat->experience_totale / $exigee) * 100);
    }

    /** Compare le niveau d'études exigé au diplôme déclaré par le candidat. */
    private function voletDiplome(Candidat $candidat, OffreEmploi $offre): float
    {
        if ($offre->niveau_etude === null) {
            return 100.0;
        }

        $anneesCandidat = $this->anneesDepuisDiplome($candidat->diplome);

        if ($anneesCandidat === null) {
            return 100.0;
        }

        $anneesExigees = $offre->niveau_etude->annees();

        if ($anneesCandidat >= $anneesExigees) {
            return 100.0;
        }

        // Chaque année manquante retire 20 points de pourcentage.
        return max(0.0, 100.0 - 20.0 * ($anneesExigees - $anneesCandidat));
    }

    /** Déduit un niveau en années après le baccalauréat à partir du libellé du diplôme. */
    private function anneesDepuisDiplome(?string $diplome): ?int
    {
        if ($diplome === null || trim($diplome) === '') {
            return null;
        }

        $texte = mb_strtolower($diplome);

        // « bac +5 », « bac + 5 », « bac5 »
        if (preg_match('/bac\s*\+?\s*(\d)/u', $texte, $correspondance) === 1) {
            return (int) $correspondance[1];
        }

        foreach ($this->equivalences() as $annees => $mots) {
            foreach ($mots as $mot) {
                if (str_contains($texte, $mot)) {
                    return $annees;
                }
            }
        }

        return null;
    }

    /** @return array<int, array<int, string>> */
    private function equivalences(): array
    {
        return [
            8 => ['doctorat', 'phd'],
            5 => ['ingénieur', 'ingenieur', 'master', 'mastère', 'mastere'],
            3 => ['licence', 'bachelor'],
            2 => ['dut', 'bts', 'deug'],
            0 => ['baccalauréat', 'baccalaureat'],
        ];
    }

    /** Tout score reste compris entre 0 et 100 (RG39). */
    private function borner(float $score): float
    {
        return round(max(0.0, min(100.0, $score)), 2);
    }
}
