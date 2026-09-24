<?php

namespace Tests\Feature;

use App\Enums\CategorieCompetence;
use App\Models\Candidat;
use App\Models\Competence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_authentifie_peut_lister_les_competences(): void
    {
        Competence::factory()->count(3)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/competences')
            ->assertOk()
            ->assertJsonStructure([
                'competences' => [['id_competence', 'nom', 'categorie', 'categorie_libelle', 'description']],
                'pagination'  => ['page_courante', 'par_page', 'total', 'derniere_page'],
            ])
            ->assertJsonCount(3, 'competences');
    }

    public function test_la_liste_des_competences_est_paginee_par_quinze(): void
    {
        Competence::factory()->count(20)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/competences')
            ->assertOk()
            ->assertJsonCount(15, 'competences')
            ->assertJsonPath('pagination.total', 20);
    }

    public function test_la_liste_peut_etre_filtree_par_categorie(): void
    {
        Competence::factory()->categorie(CategorieCompetence::Langage)->create(['nom' => 'PHP']);
        Competence::factory()->categorie(CategorieCompetence::Langue)->create(['nom' => 'Anglais']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/competences?categorie=langage')
            ->assertOk()
            ->assertJsonCount(1, 'competences')
            ->assertJsonPath('competences.0.nom', 'PHP');
    }

    public function test_la_liste_peut_etre_filtree_par_nom(): void
    {
        Competence::factory()->create(['nom' => 'Laravel']);
        Competence::factory()->create(['nom' => 'Symfony']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/competences?recherche=Lara')
            ->assertOk()
            ->assertJsonCount(1, 'competences')
            ->assertJsonPath('competences.0.nom', 'Laravel');
    }

    public function test_une_categorie_inconnue_est_rejetee(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/competences?categorie=inexistante')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categorie');
    }

    public function test_les_categories_disponibles_sont_exposees(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/competences/categories')
            ->assertOk()
            ->assertJsonCount(count(CategorieCompetence::cases()), 'categories')
            ->assertJsonStructure(['categories' => [['valeur', 'libelle']]]);
    }

    public function test_la_liste_des_competences_exige_une_authentification(): void
    {
        $this->getJson('/api/competences')->assertUnauthorized();
    }

    public function test_une_competence_inexistante_retourne_404(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/competences/9999')
            ->assertNotFound();
    }

    public function test_un_administrateur_peut_creer_une_competence(): void
    {
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/competences', [
                'nom'         => 'Kotlin',
                'categorie'   => 'langage',
                'description' => 'Langage de la plateforme JVM.',
            ])
            ->assertCreated()
            ->assertJsonPath('competence.nom', 'Kotlin')
            ->assertJsonPath('competence.categorie_libelle', 'Langage de programmation');

        $this->assertDatabaseHas('competences', ['nom' => 'Kotlin', 'categorie' => 'langage']);
    }

    public function test_la_creation_exige_un_nom_et_une_categorie(): void
    {
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/competences', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nom', 'categorie']);
    }

    public function test_le_nom_d_une_competence_est_unique(): void
    {
        Competence::factory()->create(['nom' => 'Docker']);

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/competences', ['nom' => 'Docker', 'categorie' => 'outil'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nom');
    }

    public function test_une_categorie_invalide_est_refusee_a_la_creation(): void
    {
        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->postJson('/api/competences', ['nom' => 'Rust', 'categorie' => 'inexistante'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categorie');
    }

    public function test_un_recruteur_ne_peut_pas_creer_une_competence(): void
    {
        $this->actingAs(User::factory()->recruteur()->create(), 'sanctum')
            ->postJson('/api/competences', ['nom' => 'Interdite', 'categorie' => 'outil'])
            ->assertForbidden();

        $this->assertDatabaseMissing('competences', ['nom' => 'Interdite']);
    }

    public function test_un_candidat_ne_peut_pas_creer_une_competence(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/competences', ['nom' => 'Interdite', 'categorie' => 'outil'])
            ->assertForbidden();
    }

    public function test_un_administrateur_peut_modifier_une_competence(): void
    {
        $competence = Competence::factory()->create(['nom' => 'VueJS']);

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->patchJson("/api/competences/{$competence->id_competence}", ['nom' => 'Vue.js'])
            ->assertOk()
            ->assertJsonPath('competence.nom', 'Vue.js');
    }

    public function test_la_modification_conserve_le_nom_de_la_competence_courante(): void
    {
        $competence = Competence::factory()->create(['nom' => 'Redis']);

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->patchJson("/api/competences/{$competence->id_competence}", [
                'nom'         => 'Redis',
                'description' => 'Stockage clé-valeur en mémoire.',
            ])
            ->assertOk();
    }

    public function test_un_recruteur_ne_peut_pas_modifier_une_competence(): void
    {
        $competence = Competence::factory()->create();

        $this->actingAs(User::factory()->recruteur()->create(), 'sanctum')
            ->patchJson("/api/competences/{$competence->id_competence}", ['nom' => 'Pirate'])
            ->assertForbidden();
    }

    public function test_un_administrateur_peut_supprimer_une_competence(): void
    {
        $competence = Competence::factory()->create();

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->deleteJson("/api/competences/{$competence->id_competence}")
            ->assertNoContent();

        $this->assertDatabaseMissing('competences', ['id_competence' => $competence->id_competence]);
    }

    public function test_une_competence_declaree_ne_peut_pas_etre_supprimee(): void
    {
        $competence = Competence::factory()->create();
        $candidat = Candidat::factory()->create();
        $candidat->competences()->attach($competence, [
            'niveau' => 'intermediaire',
            'annees_experience' => 2,
        ]);

        $this->actingAs(User::factory()->administrateur()->create(), 'sanctum')
            ->deleteJson("/api/competences/{$competence->id_competence}")
            ->assertStatus(409);

        $this->assertDatabaseHas('competences', ['id_competence' => $competence->id_competence]);
    }

    public function test_un_recruteur_ne_peut_pas_supprimer_une_competence(): void
    {
        $competence = Competence::factory()->create();

        $this->actingAs(User::factory()->recruteur()->create(), 'sanctum')
            ->deleteJson("/api/competences/{$competence->id_competence}")
            ->assertForbidden();

        $this->assertDatabaseHas('competences', ['id_competence' => $competence->id_competence]);
    }

    public function test_la_creation_exige_une_authentification(): void
    {
        $this->postJson('/api/competences', ['nom' => 'Rust', 'categorie' => 'langage'])
            ->assertUnauthorized();
    }
}
