<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ia\ClasserCvsRequest;
use App\Http\Requests\Ia\ConverserRequest;
use App\Http\Requests\Ia\EvaluerCvRequest;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Module d'évaluation de CV par Gemini (Nilam).
 *
 * Outil d'aide au recruteur : il évalue des CV déposés à la volée face à une
 * description de poste, sans rien enregistrer. Il ne remplace pas le score
 * d'une candidature, qui reste calculé par ScoringService (RG40, CLAUDE.md §8).
 */
class AiRecruitmentController extends Controller
{
    public function __construct(protected GeminiService $gemini) {}

    /** POST /api/ia/evaluer-cv — un CV face à une offre. */
    public function evaluate(EvaluerCvRequest $request): JsonResponse
    {
        if ($reponse = $this->indisponible()) {
            return $reponse;
        }

        try {
            $result = $this->gemini->evaluateCv(
                $request->file('cv_file')->getRealPath(),
                $request->input('job_description'),
                $request->input('job_title'),
            );
        } catch (RuntimeException $e) {
            report($e);

            return $this->echecAmont();
        }

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /** POST /api/ia/classer-cvs — plusieurs CV classés du meilleur au moins bon. */
    public function rank(ClasserCvsRequest $request): JsonResponse
    {
        if ($reponse = $this->indisponible()) {
            return $reponse;
        }

        set_time_limit(300); // chaque CV prend quelques secondes

        $evaluated = [];
        $failed = [];

        foreach ($request->file('cv_files') as $file) {
            $name = $file->getClientOriginalName();

            try {
                $result = $this->gemini->evaluateCv(
                    $file->getRealPath(),
                    $request->input('job_description'),
                    $request->input('job_title'),
                );
                $evaluated[] = ['cv_file' => $name] + $result;
            } catch (RuntimeException $e) {
                report($e);
                $failed[] = ['cv_file' => $name, 'error' => "L'évaluation de ce CV a échoué."];
            }
        }

        // Meilleur en premier (le tri de PHP est stable : les ex æquo gardent l'ordre de dépôt).
        usort($evaluated, fn ($a, $b) => $b['match_score'] <=> $a['match_score']);

        $top = (int) $request->input('top', count($evaluated));
        $ranking = [];
        foreach (array_slice($evaluated, 0, $top) as $index => $candidate) {
            $ranking[] = ['rank' => $index + 1] + $candidate;
        }

        return response()->json([
            'status' => 'success',
            'total_received' => count($request->file('cv_files')),
            'total_evaluated' => count($evaluated),
            'top' => $top,
            'ranking' => $ranking,
            'failed' => $failed,
        ]);
    }

    /** POST /api/ia/assistant — assistant des pages « Assistant IA ». */
    public function chat(ConverserRequest $request): JsonResponse
    {
        if ($reponse = $this->indisponible()) {
            return $reponse;
        }

        try {
            $texte = $this->gemini->chat(
                $request->input('message'),
                $request->user()->role->value,
            );
        } catch (RuntimeException $e) {
            report($e);

            return $this->echecAmont();
        }

        return response()->json(['status' => 'success', 'data' => ['reponse' => $texte]]);
    }

    /** Sans clé API, aucun appel n'est tenté : 503 explicite plutôt qu'une erreur opaque. */
    private function indisponible(): ?JsonResponse
    {
        if ($this->gemini->estConfigure()) {
            return null;
        }

        return response()->json([
            'status' => 'error',
            'message' => "L'assistant IA n'est pas configuré sur ce serveur (GEMINI_API_KEY absente).",
        ], 503);
    }

    /** Le détail technique part dans les journaux, pas vers le client. */
    private function echecAmont(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => "Le service d'IA n'a pas pu répondre. Réessayez dans un instant.",
        ], 502);
    }
}
