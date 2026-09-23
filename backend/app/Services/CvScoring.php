<?php

namespace App\Services;

/**
 * Turns the model's sub-scores into the final 1-100 score.
 *
 * Gemini rates four criteria (0-100 each). The weighted average is computed
 * here, in code, so every candidate is scored with exactly the same formula
 * and you can tune the weights without touching the prompt.
 */
final class CvScoring
{
    /** Weight of each sub-score. Must add up to 1.0. */
    public const WEIGHTS = [
        'skills_score' => 0.40,
        'experience_score' => 0.35,
        'education_score' => 0.15,
        'additional_score' => 0.10,
    ];

    /**
     * @param  array<string, int|float|string|null>  $subScores
     */
    public static function finalScore(array $subScores): int
    {
        $total = 0.0;

        foreach (self::WEIGHTS as $criterion => $weight) {
            $value = max(0, min(100, (int) ($subScores[$criterion] ?? 0)));
            $total += $value * $weight;
        }

        return max(1, min(100, (int) round($total)));
    }

    public static function eligibility(int $score, bool $meetsMandatoryRequirements): string
    {
        if (! $meetsMandatoryRequirements) {
            return 'Not eligible - missing mandatory requirements';
        }

        return match (true) {
            $score >= 80 => 'Strong match',
            $score >= 65 => 'Good match',
            $score >= 45 => 'Partial match',
            default => 'Weak match',
        };
    }
}
