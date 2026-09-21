<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AiRecruitmentController extends Controller
{
    public function __construct(protected GeminiService $gemini) {}

    /**
     * POST /api/evaluate-cv   (ONE cv)
     * Form-Data: cv_file (PDF), job_description (text), job_title (optional text)
     */
    public function evaluate(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'job_description' => ['required', 'string'],
            'job_title' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->gemini->evaluateCv(
                $request->file('cv_file')->getRealPath(),
                $request->input('job_description'),
                $request->input('job_title'),
            );
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 502);
        }

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * POST /api/rank-cvs   (MANY cvs -> ranked top list)
     * Form-Data: cv_files[] (PDFs, max 20), job_description (text),
     *            job_title (optional), top (optional: only return the best N, default = everyone)
     *
     * Nothing is saved: the backend stores the returned JSON however it wants.
     */
    public function rank(Request $request): JsonResponse
    {
        $request->validate([
            'cv_files' => ['required', 'array', 'min:1', 'max:20'],
            'cv_files.*' => ['file', 'mimes:pdf', 'max:10240'],
            'job_description' => ['required', 'string'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'top' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        set_time_limit(300); // each CV takes a few seconds

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
                $failed[] = ['cv_file' => $name, 'error' => $e->getMessage()];
            }
        }

        // Best first (PHP's sort is stable, so ties keep upload order).
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

    /**
     * POST /api/chat
     * JSON Payload: { "message": "How do I apply?" }
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate(['message' => ['required', 'string']]);

        return response()->json([
            'status' => 'success',
            'data' => $this->gemini->chat($request->input('message')),
        ]);
    }
}
