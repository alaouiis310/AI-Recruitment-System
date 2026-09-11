<?php

namespace App\Services;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Competence;
use App\Models\OffreEmploi;

/**
 * RG40 — calcul du score de compatibilité entre un candidat et une offre.
 *
 * Ce service est **entièrement déterministe** : aucune valeur ne provient
 * d'un modèle de langage. C'est la contrainte de conception la plus
 * importante du projet — le score doit pouvoir être expliqué ligne à ligne
 * à la soutenance, et rester calculable lorsque l'API est indisponible.
 *
 * Les mêmes entrées produisent toujours le même score.
 */
class ScoringService
{
    /** Pondération des trois volets. Leur somme vaut 1. */
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
     * RG19/RG21/RG24 — confronte les compétences déclarées par le candidat
     * (pivot posseder) à celles exigées par l'offre (pivot requerir).
     *
     * Chaque compétence exigée vaut son poids d'importance ; le candidat en
     * obtient une fraction selon l'écart entre son niveau et celui exigé. Le
     * score est le rapport des points obtenus aux points possibles.
     *
     * RG41 — les compétences non déclarées, ou déclarées en deçà du niveau
     * exigé, sont retournées pour être signalées au recruteur.
     *
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     */
    private function voletCompetences(Candidat $candidat, OffreEmploi $offre): array
    {
        $exigees = $offre->competences;

        // Une offre sans exigence ne départage pas les candidats : le volet
        // est neutre plutôt que nul.
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

    /** RG41 — description d'une compétence absente ou insuffisante. */
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

    /**
     * Fraction des points accordée selon l'écart de niveau.
     *
     * Atteindre ou dépasser le niveau exigé vaut la totalité ; en deçà, la
     * pénalité est de 25 points de pourcentage par cran manquant.
     */
    private function ratioNiveau(NiveauCompetence $possede, NiveauCompetence $requis): float
    {
        $ecart = $requis->rang() - $possede->rang();

        if ($ecart <= 0) {
            return 1.0;
        }

        return max(0.0, 1.0 - 0.25 * $ecart);
    }

    /**
     * Compare l'expérience totale du candidat à celle exigée par l'offre.
     *
     * Une offre sans exigence donne le volet à tout le monde. Au-delà du
     * seuil, le score est plafonné : dépasser largement l'attente n'apporte
     * pas de bonus, l'écart se jouant sur les compétences.
     */
    private function voletExperience(Candidat $candidat, OffreEmploi $offre): float
    {
        $exigee = (float) $offre->experience_min;

        if ($exigee <= 0.0) {
            return 100.0;
        }

        return min(100.0, ((float) $candidat->experience_totale / $exigee) * 100);
    }

    /**
     * Compare le niveau d'études exigé au diplôme déclaré par le candidat.
     *
     * Le diplôme est une chaîne libre : on en déduit un nombre d'années après
     * le baccalauréat. Faute d'indication exploitable, le volet est neutre —
     * un diplôme non reconnu ne doit pas pénaliser le candidat sur un critère
     * que la base ne permet pas de trancher.
     */
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

    /**
     * Déduit un niveau en années après le baccalauréat à partir du libellé du
     * diplôme. Reconnaît les formulations françaises les plus courantes.
     */
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

    /** RG39 — tout score reste compris entre 0 et 100. */
    private function borner(float $score): float
    {
        return round(max(0.0, min(100.0, $score)), 2);
    }
}
