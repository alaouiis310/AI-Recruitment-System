<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RankCvsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-key']);
    }

    private function reply(string $name, int $score): array
    {
        return ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode([
            'candidate_name' => $name,
            'skills_score' => $score, 'experience_score' => $score,
            'education_score' => $score, 'additional_score' => $score,
            'meets_mandatory_requirements' => true,
            'matching_skills' => ['PHP'], 'missing_skills' => [],
            'strengths' => ['Strong PHP'], 'weaknesses' => ['No cloud experience'], 'red_flags' => [],
            'summary_feedback' => 'ok',
        ])]]]]]];
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 50, 'application/pdf');
    }

    public function test_cvs_are_ranked_best_first_and_top_is_respected(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->reply('Alice', 50))
            ->push($this->reply('Bob', 95))
            ->push($this->reply('Carol', 75))]);

        $response = $this->post('/api/rank-cvs', [
            'job_description' => 'Laravel developer, 3+ years',
            'top' => 2,
            'cv_files' => [$this->pdf('a.pdf'), $this->pdf('b.pdf'), $this->pdf('c.pdf')],
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('total_evaluated', 3)
            ->assertJsonCount(2, 'ranking')
            ->assertJsonPath('ranking.0.rank', 1)
            ->assertJsonPath('ranking.0.candidate_name', 'Bob')
            ->assertJsonPath('ranking.0.match_score', 95)
            ->assertJsonPath('ranking.0.strengths.0', 'Strong PHP')
            ->assertJsonPath('ranking.0.weaknesses.0', 'No cloud experience')
            ->assertJsonPath('ranking.1.candidate_name', 'Carol');

        Http::assertSent(fn ($r) => $r->hasHeader('x-goog-api-key', 'test-key')
            && str_contains($r->body(), 'Laravel developer')
            && str_contains($r->body(), now()->toDateString()));
    }

    public function test_a_failing_cv_is_reported_and_does_not_stop_the_others(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'bad request']], 400)
            ->push($this->reply('Bob', 80))]);

        $this->post('/api/rank-cvs', [
            'job_description' => 'Laravel developer',
            'cv_files' => [$this->pdf('bad.pdf'), $this->pdf('good.pdf')],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('ranking.0.candidate_name', 'Bob')
            ->assertJsonPath('failed.0.cv_file', 'bad.pdf');
    }

    public function test_everyone_is_ranked_when_top_is_not_given(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->reply('Alice', 60))
            ->push($this->reply('Bob', 90))]);

        $this->post('/api/rank-cvs', [
            'job_description' => 'Laravel developer',
            'cv_files' => [$this->pdf('a.pdf'), $this->pdf('b.pdf')],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonCount(2, 'ranking')
            ->assertJsonPath('ranking.0.candidate_name', 'Bob')
            ->assertJsonPath('ranking.1.rank', 2);
    }

    public function test_non_pdf_is_rejected(): void
    {
        $this->post('/api/rank-cvs', [
            'job_description' => 'x',
            'cv_files' => [UploadedFile::fake()->create('cv.txt', 5, 'text/plain')],
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }
}