<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Accès et robustesse du module IA de Nilam une fois intégré au backend. */
class AssistantIaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-key']);
    }

    private function reponseGemini(string $texte): array
    {
        return ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $texte]]]]]];
    }

    public function test_l_assistant_renvoie_le_texte_de_la_reponse(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reponseGemini('Déposez votre CV depuis la page CV.'))]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/ia/assistant', ['message' => 'Comment déposer mon CV ?'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.reponse', 'Déposez votre CV depuis la page CV.');
    }

    public function test_l_assistant_adapte_son_contexte_au_role(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reponseGemini('ok'))]);

        $this->actingAs(User::factory()->recruteur()->create(), 'sanctum')
            ->postJson('/api/ia/assistant', ['message' => 'Bonjour'])
            ->assertOk();

        Http::assertSent(fn ($requete) => str_contains($requete->body(), 'recruteur'));
    }

    public function test_une_panne_de_gemini_renvoie_502_sans_detail_technique(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 400)]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/ia/assistant', ['message' => 'Bonjour'])
            ->assertStatus(502)
            ->assertJsonMissing(['message' => 'quota exceeded']);
    }

    public function test_sans_cle_api_le_service_renvoie_503_sans_appel(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/ia/assistant', ['message' => 'Bonjour'])
            ->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_le_message_est_obligatoire(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/ia/assistant', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    public function test_l_assistant_exige_une_authentification(): void
    {
        $this->postJson('/api/ia/assistant', ['message' => 'Bonjour'])->assertUnauthorized();
    }

    public function test_l_evaluation_de_cv_exige_une_authentification(): void
    {
        $this->postJson('/api/ia/evaluer-cv', [])->assertUnauthorized();
        $this->postJson('/api/ia/classer-cvs', [])->assertUnauthorized();
    }

    public function test_un_candidat_ne_peut_pas_classer_des_cv(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->post('/api/ia/classer-cvs', [
                'job_description' => 'Développeur Laravel',
                'cv_files' => [UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')],
            ], ['Accept' => 'application/json'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_un_administrateur_peut_evaluer_un_cv(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reponseGemini(json_encode([
            'candidate_name' => 'Youssef Alami',
            'skills_score' => 80, 'experience_score' => 70,
            'education_score' => 90, 'additional_score' => 60,
            'meets_mandatory_requirements' => true,
            'matching_skills' => ['PHP'], 'missing_skills' => ['Docker'],
            'strengths' => ['Laravel'], 'weaknesses' => [], 'red_flags' => [],
            'summary_feedback' => 'Bon profil.',
        ])))]);

        // 80*0.40 + 70*0.35 + 90*0.15 + 60*0.10 = 76 : le calcul reste en PHP.
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->post('/api/ia/evaluer-cv', [
                'job_description' => 'Développeur Laravel',
                'cv_file' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.match_score', 76);
    }
}
