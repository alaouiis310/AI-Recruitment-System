<?php

namespace Tests\Feature;

use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\Entretien;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntretienTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateurRecruteur;

    private Recruteur $recruteur;

    private Candidature $candidature;

    private User $utilisateurCandidat;

    protected function setUp(): void
    {
        parent::setUp();

        $entreprise  = Entreprise::factory()->create();
        $departement = Departement::factory()->pour($entreprise)->create();

        $this->utilisateurRecruteur = User::factory()->recruteur()->create();
        $this->recruteur = Recruteur::factory()->create([
            'id_user'       => $this->utilisateurRecruteur->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);
        $this->utilisateurRecruteur->refresh();

        $this->utilisateurCandidat = User::factory()->create();
        $candidat = Candidat::factory()->create(['id_user' => $this->utilisateurCandidat->id]);
        $this->utilisateurCandidat->refresh();

        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $departement)->create();

        $this->candidature = Candidature::factory()->pour($candidat, $offre)->create();
    }

    /** Recruteur d'une autre entreprise, avec sa propre candidature. */
    private function autreRecruteur(): User
    {
        $entreprise  = Entreprise::factory()->create();
        $user        = User::factory()->recruteur()->create();

        Recruteur::factory()->create([
            'id_user'       => $user->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);

        return $user->refresh();
    }

    private function donneesValides(array $ecrasements = []): array
    {
        return array_merge([
            'date'           => now()->addWeek()->toDateString(),
            'heure'          => '14:30',
            'mode'           => ModeEntretien::Visio->value,
            'lien_si_online' => 'https://meet.example.ma/abc',
        ], $ecrasements);
    }

    // -------------------------------------------------------------------
    // Planification — RG34, RG35, RG36
    // -------------------------------------------------------------------

    public function test_un_recruteur_peut_planifier_un_entretien(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens", $this->donneesValides())
            ->assertCreated()
            ->assertJsonPath('entretien.mode', ModeEntretien::Visio->value)
            ->assertJsonPath('entretien.resultat', ResultatEntretien::EnAttente->value);

        $this->assertDatabaseHas('entretiens', [
            'id_candidature' => $this->candidature->id_candidature,
        ]);
    }

    public function test_une_candidature_peut_compter_plusieurs_entretiens(): void
    {
        // RG34 — zero, un ou plusieurs.
        Entretien::factory()->count(3)->pour($this->candidature)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens")
            ->assertOk()
            ->assertJsonCount(3, 'entretiens');
    }

    public function test_la_planification_exige_une_date_et_une_heure(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens", [
                'mode' => ModeEntretien::Presentiel->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'heure']);
    }

    public function test_un_entretien_ne_peut_pas_etre_planifie_dans_le_passe(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens",
                $this->donneesValides(['date' => now()->subDay()->toDateString()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_un_entretien_en_visio_exige_un_lien(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens",
                $this->donneesValides(['lien_si_online' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lien_si_online');
    }

    public function test_un_entretien_en_presentiel_se_passe_de_lien(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens",
                $this->donneesValides(['mode' => ModeEntretien::Presentiel->value, 'lien_si_online' => null]))
            ->assertCreated();
    }

    public function test_un_mode_inconnu_est_refuse(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens",
                $this->donneesValides(['mode' => 'telepathie']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mode');
    }

    // -------------------------------------------------------------------
    // Propriete — RG14
    // -------------------------------------------------------------------

    public function test_un_recruteur_ne_planifie_pas_sur_la_candidature_d_un_autre(): void
    {
        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->postJson("/api/recruteur/candidatures/{$this->candidature->id_candidature}/entretiens", $this->donneesValides())
            ->assertForbidden();

        $this->assertDatabaseCount('entretiens', 0);
    }

    public function test_un_recruteur_ne_voit_que_les_entretiens_de_ses_offres(): void
    {
        Entretien::factory()->pour($this->candidature)->create();

        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->getJson('/api/recruteur/entretiens')
            ->assertOk()
            ->assertJsonCount(0, 'entretiens');

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/entretiens')
            ->assertOk()
            ->assertJsonCount(1, 'entretiens');
    }

    public function test_un_recruteur_ne_modifie_pas_l_entretien_d_un_autre(): void
    {
        $entretien = Entretien::factory()->pour($this->candidature)->create();

        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->patchJson("/api/recruteur/entretiens/{$entretien->id_entretien}", [
                'resultat' => ResultatEntretien::Favorable->value,
            ])
            ->assertForbidden();
    }

    public function test_un_candidat_consulte_les_entretiens_de_sa_candidature(): void
    {
        Entretien::factory()->pour($this->candidature)->create();

        $this->actingAs($this->utilisateurCandidat, 'sanctum')
            ->getJson("/api/candidat/candidatures/{$this->candidature->id_candidature}/entretiens")
            ->assertOk()
            ->assertJsonCount(1, 'entretiens');
    }

    public function test_un_candidat_ne_consulte_pas_les_entretiens_d_un_autre(): void
    {
        Entretien::factory()->pour($this->candidature)->create();

        $autre = User::factory()->create();
        Candidat::factory()->create(['id_user' => $autre->id]);

        $this->actingAs($autre->refresh(), 'sanctum')
            ->getJson("/api/candidat/candidatures/{$this->candidature->id_candidature}/entretiens")
            ->assertForbidden();
    }

    // -------------------------------------------------------------------
    // Issue de l'entretien — RG36
    // -------------------------------------------------------------------

    public function test_un_recruteur_renseigne_l_issue_de_l_entretien(): void
    {
        $entretien = Entretien::factory()->pour($this->candidature)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/entretiens/{$entretien->id_entretien}", [
                'resultat'    => ResultatEntretien::Favorable->value,
                'commentaire' => 'Candidat convaincant sur la partie technique.',
            ])
            ->assertOk()
            ->assertJsonPath('entretien.resultat', ResultatEntretien::Favorable->value);
    }

    public function test_l_issue_d_un_entretien_passe_peut_etre_renseignee(): void
    {
        $entretien = Entretien::factory()->pour($this->candidature)->tenu()->create();

        // Une date passee est acceptee a la mise a jour : l'entretien a eu lieu.
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/entretiens/{$entretien->id_entretien}", [
                'resultat' => ResultatEntretien::Defavorable->value,
            ])
            ->assertOk();
    }

    public function test_un_recruteur_peut_annuler_un_entretien(): void
    {
        $entretien = Entretien::factory()->pour($this->candidature)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->deleteJson("/api/recruteur/entretiens/{$entretien->id_entretien}")
            ->assertNoContent();

        $this->assertDatabaseCount('entretiens', 0);
    }

    public function test_la_consultation_exige_une_authentification(): void
    {
        $this->getJson('/api/recruteur/entretiens')->assertUnauthorized();
    }
}
