<?php

namespace App\Services\Ia;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Accès au modèle de langage (RG37, RG42).
 *
 * Deux usages seulement : extraire le contenu d'un CV et rédiger un résumé.
 * Le score de compatibilité n'est jamais demandé au modèle — il est calculé
 * par ScoringService (RG40).
 *
 * Toute indisponibilité est absorbée ici : les méthodes renvoient null plutôt
 * que de lever, afin que l'analyse aboutisse malgré tout.
 */
class ClientIa
{
    private ?Client $client = null;

    /** Le module fonctionne sans clé : l'analyse se limite alors au calcul. */
    public function estDisponible(): bool
    {
        return filled(config('ia.cle_api'));
    }

    /**
     * RG37 — extrait du texte d'un CV les compétences, l'ancienneté et le
     * diplôme. Renvoie null si le modèle est indisponible ou illisible.
     */
    public function extraireCv(string $texteCv): ?ResultatExtraction
    {
        $reponse = $this->demander(
            'Tu analyses un CV pour un service de recrutement. Réponds uniquement '
            .'par un objet JSON, sans texte autour, de la forme : '
            .'{"competences": ["..."], "annees_experience": 0, "diplome": "...", "resume": "..."}. '
            .'Le résumé fait au plus trois phrases, en français.',
            "Voici le contenu du CV :\n\n".$texteCv,
        );

        if ($reponse === null) {
            return null;
        }

        $donnees = $this->decoderJson($reponse);

        if ($donnees === null) {
            return null;
        }

        return new ResultatExtraction(
            competencesDetectees: array_values(array_filter(
                (array) ($donnees['competences'] ?? []),
                static fn ($v) => is_string($v) && $v !== '',
            )),
            anneesExperience: isset($donnees['annees_experience']) && is_numeric($donnees['annees_experience'])
                ? (float) $donnees['annees_experience']
                : null,
            diplome: is_string($donnees['diplome'] ?? null) ? $donnees['diplome'] : null,
            resume: is_string($donnees['resume'] ?? null) ? $donnees['resume'] : null,
        );
    }

    /**
     * RG42 — rédige le commentaire présenté au recruteur.
     *
     * Les scores sont fournis en entrée : le modèle les met en mots, il ne
     * les produit pas et n'est pas autorisé à les contredire.
     */
    public function redigerResume(string $contexte): ?string
    {
        return $this->demander(
            'Tu rédiges, en français, une synthèse de trois à quatre phrases à '
            ."destination d'un recruteur. Les scores te sont donnés : reprends-les "
            .'tels quels, ne les recalcule pas et ne les contredis pas. Sois factuel, '
            .'sans formule de politesse.',
            $contexte,
        );
    }

    /** Appel unitaire au modèle. Renvoie null en cas d'indisponibilité. */
    private function demander(string $consigne, string $message): ?string
    {
        if (! $this->estDisponible()) {
            return null;
        }

        try {
            $reponse = $this->client()->messages->create(
                model: config('ia.modele'),
                maxTokens: config('ia.max_tokens'),
                system: $consigne,
                messages: [['role' => 'user', 'content' => $message]],
            );

            foreach ($reponse->content as $bloc) {
                if ($bloc->type === 'text') {
                    return $bloc->text;
                }
            }

            return null;
        } catch (Throwable $e) {
            // L'analyse doit aboutir même API indisponible : on trace et on
            // rend la main au calcul déterministe.
            Log::warning("Appel au modèle de langage impossible : {$e->getMessage()}");

            return null;
        }
    }

    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: config('ia.cle_api'));
    }

    /** Le modèle encadre parfois le JSON d'un bloc de code : on le retire. */
    private function decoderJson(string $reponse): ?array
    {
        $texte = trim($reponse);
        $texte = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $texte) ?? $texte;

        $donnees = json_decode($texte, true);

        return is_array($donnees) ? $donnees : null;
    }
}
