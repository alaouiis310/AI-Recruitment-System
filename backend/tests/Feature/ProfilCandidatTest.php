<?php

namespace Tests\Feature;

use App\Enums\NiveauCompetence;
use App\Models\Candidat;
use App\Models\Competence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilCandidatTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateur;

    private Candidat $candidat;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->utilisateur = User::factory()->create();
        $this->candidat    = Candidat::factory()->create(['id_user' => $this->utilisateur->id]);

        // Sans rafraichissement, la relation candidat peut rester en cache a null.
        $this->utilisateur->refresh();
    }

    // -------------------------------------------------------------------
    // CV — RG22, RG23
    // -------------------------------------------------------------------

    public function test_un_candidat_peut_deposer_son_cv(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/cv', [
                'cv' => UploadedFile::fake()->create('cv.pdf', 400, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'CV enregistré.');

        $chemin = $this->candidat->fresh()->cv_pdf;

        $this->assertNotNull($chemin);
        Storage::disk('public')->assertExists($chemin);
    }

    public function test_le_depot_d_un_nouveau_cv_efface_le_precedent(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/cv', ['cv' => UploadedFile::fake()->create('ancien.pdf', 100, 'application/pdf')])
            ->assertOk();

        $ancien = $this->candidat->fresh()->cv_pdf;

        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/cv', ['cv' => UploadedFile::fake()->create('nouveau.pdf', 100, 'application/pdf')])
            ->assertOk();

        $nouveau = $this->candidat->fresh()->cv_pdf;

        $this->assertNotSame($ancien, $nouveau);
        Storage::disk('public')->assertMissing($ancien);
        Storage::disk('public')->assertExists($nouveau);
    }

    public function test_le_cv_doit_etre_un_pdf(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/cv', ['cv' => UploadedFile::fake()->create('cv.docx', 100)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cv');
    }

    public function test_le_cv_ne_peut_pas_depasser_dix_mega_octets(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/cv', [
                'cv' => UploadedFile::fake()->create('cv.pdf', 11 * 1024, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cv');
    }

    public function test_un_candidat_peut_supprimer_son_cv(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/cv', ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertOk();

        $chemin = $this->candidat->fresh()->cv_pdf;

        $this->actingAs($this->utilisateur, 'sanctum')
            ->deleteJson('/api/candidat/cv')
            ->assertOk();

        $this->assertNull($this->candidat->fresh()->cv_pdf);
        Storage::disk('public')->assertMissing($chemin);
    }

    public function test_le_depot_de_cv_exige_une_authentification(): void
    {
        $this->postJson('/api/candidat/cv', ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertUnauthorized();
    }

    public function test_un_recruteur_ne_peut_pas_deposer_de_cv(): void
    {
        $this->actingAs(User::factory()->recruteur()->create(), 'sanctum')
            ->postJson('/api/candidat/cv', ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------
    // Photo
    // -------------------------------------------------------------------

    public function test_un_candidat_peut_deposer_une_photo(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/photo', ['photo' => UploadedFile::fake()->image('moi.jpg')])
            ->assertOk();

        Storage::disk('public')->assertExists($this->candidat->fresh()->photo);
    }

    public function test_la_photo_refuse_un_pdf(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/photo', ['photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    // -------------------------------------------------------------------
    // Competences declarees — RG24, RG25, RG26
    // -------------------------------------------------------------------

    public function test_un_candidat_peut_declarer_ses_competences(): void
    {
        $php   = Competence::factory()->create(['nom' => 'PHP']);
        $mysql = Competence::factory()->create(['nom' => 'MySQL']);

        $this->actingAs($this->utilisateur, 'sanctum')
            ->putJson('/api/candidat/competences', [
                'competences' => [
                    ['id_competence' => $php->id_competence,   'niveau' => NiveauCompetence::Avance->value,        'annees_experience' => 4],
                    ['id_competence' => $mysql->id_competence, 'niveau' => NiveauCompetence::Intermediaire->value, 'annees_experience' => 2.5],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'competences');

        $this->assertDatabaseHas('posseder', [
            'id_candidat'   => $this->candidat->id_candidat,
            'id_competence' => $php->id_competence,
            'niveau'        => NiveauCompetence::Avance->value,
        ]);
    }

    public function test_la_synchronisation_remplace_la_liste_complete(): void
    {
        $ancienne = Competence::factory()->create();
        $nouvelle = Competence::factory()->create();

        $this->candidat->competences()->sync([
            $ancienne->id_competence => ['niveau' => NiveauCompetence::Expert->value, 'annees_experience' => 8],
        ]);

        $this->actingAs($this->utilisateur, 'sanctum')
            ->putJson('/api/candidat/competences', [
                'competences' => [
                    ['id_competence' => $nouvelle->id_competence, 'niveau' => NiveauCompetence::Debutant->value],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'competences');

        $this->assertDatabaseMissing('posseder', ['id_competence' => $ancienne->id_competence]);
        $this->assertDatabaseHas('posseder', ['id_competence' => $nouvelle->id_competence]);
    }

    public function test_une_liste_vide_retire_toutes_les_competences(): void
    {
        $competence = Competence::factory()->create();

        $this->candidat->competences()->sync([
            $competence->id_competence => ['niveau' => NiveauCompetence::Avance->value, 'annees_experience' => 3],
        ]);

        $this->actingAs($this->utilisateur, 'sanctum')
            ->putJson('/api/candidat/competences', ['competences' => []])
            ->assertOk()
            ->assertJsonCount(0, 'competences');

        $this->assertDatabaseCount('posseder', 0);
    }

    public function test_une_competence_ne_peut_etre_declaree_qu_une_fois(): void
    {
        $php = Competence::factory()->create();

        $this->actingAs($this->utilisateur, 'sanctum')
            ->putJson('/api/candidat/competences', [
                'competences' => [
                    ['id_competence' => $php->id_competence, 'niveau' => NiveauCompetence::Avance->value],
                    ['id_competence' => $php->id_competence, 'niveau' => NiveauCompetence::Debutant->value],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('competences.0.id_competence');
    }

    public function test_la_declaration_refuse_un_niveau_inconnu(): void
    {
        $php = Competence::factory()->create();

        $this->actingAs($this->utilisateur, 'sanctum')
            ->putJson('/api/candidat/competences', [
                'competences' => [['id_competence' => $php->id_competence, 'niveau' => 'grand_maitre']],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('competences.0.niveau');
    }

    public function test_la_declaration_refuse_une_competence_hors_referentiel(): void
    {
        $this->actingAs($this->utilisateur, 'sanctum')
            ->putJson('/api/candidat/competences', [
                'competences' => [['id_competence' => 999999, 'niveau' => NiveauCompetence::Avance->value]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('competences.0.id_competence');
    }

    public function test_un_candidat_peut_ajouter_une_competence_sans_toucher_aux_autres(): void
    {
        $existante = Competence::factory()->create();
        $ajoutee   = Competence::factory()->create();

        $this->candidat->competences()->sync([
            $existante->id_competence => ['niveau' => NiveauCompetence::Avance->value, 'annees_experience' => 3],
        ]);

        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/competences', [
                'id_competence'     => $ajoutee->id_competence,
                'niveau'            => NiveauCompetence::Debutant->value,
                'annees_experience' => 1,
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'competences');

        $this->assertDatabaseHas('posseder', ['id_competence' => $existante->id_competence]);
        $this->assertDatabaseHas('posseder', ['id_competence' => $ajoutee->id_competence]);
    }

    public function test_declarer_une_competence_deja_presente_met_a_jour_son_niveau(): void
    {
        $php = Competence::factory()->create();

        $this->candidat->competences()->sync([
            $php->id_competence => ['niveau' => NiveauCompetence::Debutant->value, 'annees_experience' => 1],
        ]);

        $this->actingAs($this->utilisateur, 'sanctum')
            ->postJson('/api/candidat/competences', [
                'id_competence'     => $php->id_competence,
                'niveau'            => NiveauCompetence::Expert->value,
                'annees_experience' => 7,
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'competences');

        $this->assertDatabaseHas('posseder', [
            'id_competence' => $php->id_competence,
            'niveau'        => NiveauCompetence::Expert->value,
        ]);
    }

    public function test_un_candidat_peut_retirer_une_competence(): void
    {
        $php = Competence::factory()->create();

        $this->candidat->competences()->sync([
            $php->id_competence => ['niveau' => NiveauCompetence::Avance->value, 'annees_experience' => 3],
        ]);

        $this->actingAs($this->utilisateur, 'sanctum')
            ->deleteJson("/api/candidat/competences/{$php->id_competence}")
            ->assertNoContent();

        $this->assertDatabaseCount('posseder', 0);
    }

    public function test_un_candidat_peut_lister_ses_competences(): void
    {
        $php = Competence::factory()->create(['nom' => 'PHP']);

        $this->candidat->competences()->sync([
            $php->id_competence => ['niveau' => NiveauCompetence::Avance->value, 'annees_experience' => 4.5],
        ]);

        // La valeur decimale doit survivre au pivot et a la serialisation.
        $this->actingAs($this->utilisateur, 'sanctum')
            ->getJson('/api/candidat/competences')
            ->assertOk()
            ->assertJsonCount(1, 'competences')
            ->assertJsonPath('competences.0.nom', 'PHP')
            ->assertJsonPath('competences.0.niveau', NiveauCompetence::Avance->value)
            ->assertJsonPath('competences.0.annees_experience', 4.5);
    }

    public function test_un_recruteur_ne_peut_pas_declarer_de_competences(): void
    {
        $this->actingAs(User::factory()->recruteur()->create(), 'sanctum')
            ->putJson('/api/candidat/competences', ['competences' => []])
            ->assertForbidden();
    }

    public function test_la_liste_des_competences_exige_une_authentification(): void
    {
        $this->getJson('/api/candidat/competences')->assertUnauthorized();
    }
}
