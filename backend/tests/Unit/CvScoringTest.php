<?php

namespace Tests\Unit;

use App\Services\CvScoring;
use PHPUnit\Framework\TestCase;

class CvScoringTest extends TestCase
{
    public function test_perfect_sub_scores_give_100(): void
    {
        $this->assertSame(100, CvScoring::finalScore([
            'skills_score' => 100, 'experience_score' => 100, 'education_score' => 100, 'additional_score' => 100,
        ]));
    }

    public function test_score_never_goes_below_1(): void
    {
        $this->assertSame(1, CvScoring::finalScore([]));
    }

    public function test_weighted_average(): void
    {
        // 80*0.40 + 60*0.35 + 40*0.15 + 40*0.10 = 63
        $this->assertSame(63, CvScoring::finalScore([
            'skills_score' => 80, 'experience_score' => 60, 'education_score' => 40, 'additional_score' => 40,
        ]));
    }

    public function test_eligibility_labels(): void
    {
        $this->assertSame('Strong match', CvScoring::eligibility(80, true));
        $this->assertSame('Good match', CvScoring::eligibility(65, true));
        $this->assertSame('Partial match', CvScoring::eligibility(45, true));
        $this->assertSame('Weak match', CvScoring::eligibility(44, true));
        $this->assertStringStartsWith('Not eligible', CvScoring::eligibility(95, false));
    }
}
