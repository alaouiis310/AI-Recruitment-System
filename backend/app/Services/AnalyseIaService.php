<?php

namespace App\Services;

use App\Enums\Recommandation;
use App\Models\AnalyseIa;
use App\Models\Candidature;
use App\Services\Ia\ClientIa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

/** Analyse d'une candidature (RG37 à RG43). */
class AnalyseIaService
{
    public function __construct(
        private readonly ScoringService $scoring,
        private readonly ClientIa $ia,
    ) {}

    /**
     * Produit ou remplace l'analyse d'une candidature, et reporte le score sur la candidature pour le
     * classement de RG43 (RG37/RG38).
     */
    public function analyser(Candidature $candidature): AnalyseIa
    {
        $candidature->loadMissing(['candidat.competences', 'offre.competences']);

        // Phase 1 : extraction, facultative.
        $extraction = $this->extraireLeCv($candidature);

        // Phase 2 : calcul déterministe. Jamais sauté (RG40).
        $scores = $this->scoring->evaluer($candidature);

        $recommandation = Recommandation::depuisScore($scores['score_matching']);

        // Phase 3 : rédaction, facultative, à partir des scores déjà calculés.
        $resume = $this->ia->redigerResume($this->contexte($candidature, $scores, $recommandation))
            ?? $this->resumeDeSecours($candidature, $scores, $recommandation, $extraction?->resume);

        return DB::transaction(function () use ($candidature, $scores, $recommandation, $resume) {
            $analyse = AnalyseIa::updateOrCreate(
                ['id_candidature' => $candidature->id_candidature],
                [
                    'score_matching'         => $scores['score_matching'],
                    'score_competence'       => $scores['score_competence'],
                    'score_experience'       => $scores['score_experience'],
                    'score_diplome'          => $scores['score_diplome'],
                    'competences_manquantes' => $scores['competences_manquantes'],
                    'resume_cv'              => $resume,
                    'recommandation'         => $recommandation,
                    'date_analyse'           => now()->toDateString(),
                ],
            );

            // Le score alimente le classement des candidatures (RG43).
            $candidature->update(['score_final' => $scores['score_matching']]);

            return $analyse;
        });
    }

    /** Lit le CV du candidat et le confie au modèle (RG22/RG37). */
    private function extraireLeCv(Candidature $candidature): ?Ia\ResultatExtraction
    {
        if (! $this->ia->estDisponible() || ! $candidature->candidat->possedeUnCv()) {
            return null;
        }

        $texte = $this->texteDuCv($candidature->candidat->cv_pdf);

        return $texte === null ? null : $this->ia->extraireCv($texte);
    }

    /** Extrait le texte d'un PDF stocké sur le disque public. */
    private function texteDuCv(string $chemin): ?string
    {
        $disque = Storage::disk('public');

        if (! $disque->exists($chemin)) {
            return null;
        }

        try {
            $texte = (new Parser)->parseContent($disque->get($chemin))->getText();
        } catch (\Throwable) {
            // Un PDF illisible ou protégé ne doit pas faire échouer l'analyse.
            return null;
        }

        $texte = trim($texte);

        // Au-delà, on tronque : un CV utile tient largement dans cette limite.
        return $texte === '' ? null : mb_substr($texte, 0, 20000);
    }

    /** Contexte factuel transmis au modèle pour la rédaction (phase 3). */
    private function contexte(Candidature $candidature, array $scores, Recommandation $recommandation): string
    {
        $manquantes = collect($scores['competences_manquantes'])
            ->map(fn (array $c) => $c['niveau_actuel'] === null
                ? "{$c['nom']} (absente, niveau exigé : {$c['niveau_requis']})"
                : "{$c['nom']} (niveau {$c['niveau_actuel']}, exigé : {$c['niveau_requis']})")
            ->implode(' ; ');

        return implode("\n", [
            "Offre : {$candidature->offre->titre}",
            "Expérience exigée : {$candidature->offre->experience_min} an(s)",
            "Expérience du candidat : {$candidature->candidat->experience_totale} an(s)",
            'Diplôme du candidat : '.($candidature->candidat->diplome ?? 'non renseigné'),
            '',
            "Score global : {$scores['score_matching']}/100",
            "Score compétences : {$scores['score_competence']}/100",
            "Score expérience : {$scores['score_experience']}/100",
            "Score diplôme : {$scores['score_diplome']}/100",
            'Recommandation : '.$recommandation->libelle(),
            '',
            'Compétences non couvertes : '.($manquantes !== '' ? $manquantes : 'aucune'),
        ]);
    }

    /** Résumé rédigé sans le modèle. */
    private function resumeDeSecours(Candidature $candidature, array $scores, Recommandation $recommandation, ?string $resumeExtrait): string
    {
        $nombre = count($scores['competences_manquantes']);

        $phrases = [
            sprintf(
                'Score de compatibilité de %s/100 avec l\'offre « %s » : %s sur les compétences, %s sur l\'expérience, %s sur le diplôme.',
                $scores['score_matching'],
                $candidature->offre->titre,
                $scores['score_competence'],
                $scores['score_experience'],
                $scores['score_diplome'],
            ),
            $nombre === 0
                ? 'Toutes les compétences exigées sont couvertes au niveau demandé.'
                : sprintf('%d compétence(s) exigée(s) ne sont pas couvertes au niveau demandé : %s.', $nombre, collect($scores['competences_manquantes'])->pluck('nom')->implode(', ')),
            'Recommandation : '.$recommandation->libelle().'.',
        ];

        if ($resumeExtrait !== null) {
            array_unshift($phrases, $resumeExtrait);
        }

        return implode(' ', $phrases);
    }
}
