<?php

namespace Tests\Feature;

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use App\Models\Competence;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffreEmploiTest extends TestCase
{
    use RefreshDatabase;

    private Entreprise $entreprise;

    private Departement $departement;

    private Recruteur $recruteur;

    private User $utilisateurRecruteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entreprise  = Entreprise::factory()->create();
        $this->departement = Departement::factory()->pour($this->entreprise)->create();

        $this->utilisateurRecruteur = User::factory()->recruteur()->create();
        $this->recruteur = Recruteur::factory()->create([
            'id_user'       => $this->utilisateurRecruteur->id,
            'id_entreprise' => $this->entreprise->id_entreprise,
        ]);

        // Sans rafraichissement, la relation recruteur peut rester en cache a null.
        $this->utilisateurRecruteur->refresh();
    }

    /** Charge utile minimale valide pour la publication d'une offre. */
    private function donneesValides(array $ecrasements = []): array
    {
        return array_merge([
            'titre'          => 'Developpeur PHP',
            'description'    => 'Conception et maintenance des applications metier.',
            'type_contrat'   => TypeContrat::Cdi->value,
            'localisation'   => 'Tanger',
            'id_departement' => $this->departement->id_departement,
        ], $ecrasements);
    }

    // -------------------------------------------------------------------
    // Publication — RG12, RG13, RG15
    // -------------------------------------------------------------------

    public function test_un_recruteur_peut_publier_une_offre(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides())
            ->assertCreated()
            ->assertJsonPath('offre.titre', 'Developpeur PHP')
            ->assertJsonPath('offre.statut', StatutOffre::Ouverte->value);

        $this->assertDatabaseHas('offres_emploi', [
            'titre'        => 'Developpeur PHP',
            'id_recruteur' => $this->recruteur->id_recruteur,
        ]);
    }

    public function test_la_publication_renseigne_la_date_du_jour_par_defaut(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides())
            ->assertCreated()
            ->assertJsonPath('offre.date_publication', now()->toDateString());
    }

    public function test_la_publication_exige_un_titre(): void
    {
        $donnees = $this->donneesValides();
        unset($donnees['titre']);

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $donnees)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('titre');
    }

    public function test_la_publication_refuse_un_type_de_contrat_inconnu(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides(['type_contrat' => 'benevolat']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type_contrat');
    }

    public function test_un_recruteur_ne_peut_pas_publier_dans_le_departement_d_une_autre_entreprise(): void
    {
        $autre = Departement::factory()->pour(Entreprise::factory()->create())->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides([
                'id_departement' => $autre->id_departement,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_departement');
    }

    public function test_la_date_d_expiration_doit_suivre_la_date_de_publication(): void
    {
        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides([
                'date_publication' => now()->toDateString(),
                'date_expiration'  => now()->subWeek()->toDateString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_expiration');
    }

    public function test_un_candidat_ne_peut_pas_publier_une_offre(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides())
            ->assertForbidden();
    }

    public function test_la_publication_exige_une_authentification(): void
    {
        $this->postJson('/api/recruteur/offres', $this->donneesValides())
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------
    // Competences requises — RG19, RG21
    // -------------------------------------------------------------------

    public function test_une_offre_peut_exiger_des_competences_avec_niveau_et_importance(): void
    {
        $php   = Competence::factory()->create(['nom' => 'PHP']);
        $mysql = Competence::factory()->create(['nom' => 'MySQL']);

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides([
                'competences' => [
                    ['id_competence' => $php->id_competence,   'niveau_requis' => NiveauCompetence::Avance->value,        'importance' => ImportanceCompetence::Essentielle->value],
                    ['id_competence' => $mysql->id_competence, 'niveau_requis' => NiveauCompetence::Intermediaire->value, 'importance' => ImportanceCompetence::Souhaitee->value],
                ],
            ]))
            ->assertCreated()
            ->assertJsonCount(2, 'offre.competences');

        $this->assertDatabaseHas('requerir', [
            'id_competence' => $php->id_competence,
            'niveau_requis' => NiveauCompetence::Avance->value,
            'importance'    => ImportanceCompetence::Essentielle->value,
        ]);
    }

    public function test_une_competence_ne_peut_etre_exigee_qu_une_fois_par_offre(): void
    {
        $php = Competence::factory()->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->postJson('/api/recruteur/offres', $this->donneesValides([
                'competences' => [
                    ['id_competence' => $php->id_competence, 'niveau_requis' => NiveauCompetence::Avance->value,   'importance' => ImportanceCompetence::Essentielle->value],
                    ['id_competence' => $php->id_competence, 'niveau_requis' => NiveauCompetence::Debutant->value, 'importance' => ImportanceCompetence::Souhaitee->value],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('competences.0.id_competence');
    }

    public function test_la_modification_remplace_les_competences_transmises(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        $php   = Competence::factory()->create();
        $vue   = Competence::factory()->create();

        $offre->competences()->sync([
            $php->id_competence => [
                'niveau_requis' => NiveauCompetence::Avance->value,
                'importance'    => ImportanceCompetence::Essentielle->value,
            ],
        ]);

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/offres/{$offre->id_offre}", [
                'competences' => [
                    ['id_competence' => $vue->id_competence, 'niveau_requis' => NiveauCompetence::Debutant->value, 'importance' => ImportanceCompetence::Souhaitee->value],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'offre.competences');

        $this->assertDatabaseMissing('requerir', ['id_competence' => $php->id_competence]);
        $this->assertDatabaseHas('requerir', ['id_competence' => $vue->id_competence]);
    }

    public function test_une_modification_sans_cle_competences_laisse_le_pivot_intact(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        $php   = Competence::factory()->create();

        $offre->competences()->sync([
            $php->id_competence => [
                'niveau_requis' => NiveauCompetence::Avance->value,
                'importance'    => ImportanceCompetence::Essentielle->value,
            ],
        ]);

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/offres/{$offre->id_offre}", ['titre' => 'Titre revu'])
            ->assertOk();

        $this->assertDatabaseHas('requerir', ['id_competence' => $php->id_competence]);
    }

    // -------------------------------------------------------------------
    // Propriete de l'offre — RG12, RG13
    // -------------------------------------------------------------------

    /** Recruteur d'une autre entreprise, avec son propre departement. */
    private function autreRecruteur(): User
    {
        $entreprise = Entreprise::factory()->create();
        $user       = User::factory()->recruteur()->create();

        Recruteur::factory()->create([
            'id_user'       => $user->id,
            'id_entreprise' => $entreprise->id_entreprise,
        ]);

        return $user->refresh();
    }

    public function test_un_recruteur_peut_modifier_son_offre(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/offres/{$offre->id_offre}", ['titre' => 'Titre revu'])
            ->assertOk()
            ->assertJsonPath('offre.titre', 'Titre revu');
    }

    public function test_un_recruteur_ne_peut_pas_modifier_l_offre_d_un_autre(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create(['titre' => 'Intacte']);

        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->patchJson("/api/recruteur/offres/{$offre->id_offre}", ['titre' => 'Detournee'])
            ->assertForbidden();

        $this->assertDatabaseHas('offres_emploi', [
            'id_offre' => $offre->id_offre,
            'titre'    => 'Intacte',
        ]);
    }

    public function test_un_recruteur_peut_fermer_son_offre(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->patchJson("/api/recruteur/offres/{$offre->id_offre}", ['statut' => StatutOffre::Fermee->value])
            ->assertOk()
            ->assertJsonPath('offre.statut', StatutOffre::Fermee->value)
            ->assertJsonPath('offre.accepte_candidatures', false);
    }

    public function test_un_recruteur_peut_supprimer_son_offre(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->deleteJson("/api/recruteur/offres/{$offre->id_offre}")
            ->assertNoContent();

        $this->assertDatabaseMissing('offres_emploi', ['id_offre' => $offre->id_offre]);
    }

    public function test_un_recruteur_ne_peut_pas_supprimer_l_offre_d_un_autre(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();

        $this->actingAs($this->autreRecruteur(), 'sanctum')
            ->deleteJson("/api/recruteur/offres/{$offre->id_offre}")
            ->assertForbidden();

        $this->assertDatabaseHas('offres_emploi', ['id_offre' => $offre->id_offre]);
    }

    public function test_la_liste_du_recruteur_ne_montre_que_ses_offres(): void
    {
        OffreEmploi::factory()->count(2)->publieePar($this->recruteur, $this->departement)->create();
        OffreEmploi::factory()->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/offres')
            ->assertOk()
            ->assertJsonCount(2, 'offres')
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_la_liste_du_recruteur_montre_aussi_ses_offres_fermees(): void
    {
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->fermee()->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/offres')
            ->assertOk()
            ->assertJsonCount(2, 'offres');
    }

    public function test_la_liste_du_recruteur_peut_etre_filtree_par_statut(): void
    {
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->fermee()->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson('/api/recruteur/offres?statut='.StatutOffre::Fermee->value)
            ->assertOk()
            ->assertJsonCount(1, 'offres')
            ->assertJsonPath('offres.0.statut', StatutOffre::Fermee->value);
    }

    // -------------------------------------------------------------------
    // Consultation publique — RG15, RG17, RG18
    // -------------------------------------------------------------------

    public function test_un_candidat_peut_lister_les_offres_ouvertes(): void
    {
        OffreEmploi::factory()->count(3)->publieePar($this->recruteur, $this->departement)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/offres')
            ->assertOk()
            ->assertJsonStructure([
                'offres'     => [['id_offre', 'titre', 'type_contrat', 'localisation', 'statut', 'competences']],
                'pagination' => ['page_courante', 'par_page', 'total', 'derniere_page'],
            ])
            ->assertJsonCount(3, 'offres');
    }

    public function test_la_liste_publique_masque_les_offres_fermees_et_expirees(): void
    {
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create(['titre' => 'Visible']);
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->fermee()->create();
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->expiree()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/offres')
            ->assertOk()
            ->assertJsonCount(1, 'offres')
            ->assertJsonPath('offres.0.titre', 'Visible');
    }

    public function test_la_liste_publique_peut_etre_filtree_par_mots_cles(): void
    {
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create(['titre' => 'Developpeur PHP']);
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create(['titre' => 'Designer UI']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/offres?mots_cles=PHP')
            ->assertOk()
            ->assertJsonCount(1, 'offres')
            ->assertJsonPath('offres.0.titre', 'Developpeur PHP');
    }

    public function test_la_liste_publique_peut_etre_filtree_par_localisation_et_contrat(): void
    {
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)
            ->create(['localisation' => 'Tanger', 'type_contrat' => TypeContrat::Cdi]);
        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)
            ->create(['localisation' => 'Rabat', 'type_contrat' => TypeContrat::Stage]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/offres?localisation=Tanger&type_contrat='.TypeContrat::Cdi->value)
            ->assertOk()
            ->assertJsonCount(1, 'offres')
            ->assertJsonPath('offres.0.localisation', 'Tanger');
    }

    public function test_la_liste_publique_peut_etre_filtree_par_departement(): void
    {
        $autreDepartement = Departement::factory()->pour($this->entreprise)->create();

        OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();
        OffreEmploi::factory()->publieePar($this->recruteur, $autreDepartement)->create(['titre' => 'Ailleurs']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/offres?id_departement='.$autreDepartement->id_departement)
            ->assertOk()
            ->assertJsonCount(1, 'offres')
            ->assertJsonPath('offres.0.titre', 'Ailleurs');
    }

    public function test_la_liste_publique_est_paginee(): void
    {
        OffreEmploi::factory()->count(18)->publieePar($this->recruteur, $this->departement)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/offres')
            ->assertOk()
            ->assertJsonCount(15, 'offres')
            ->assertJsonPath('pagination.total', 18)
            ->assertJsonPath('pagination.derniere_page', 2);
    }

    public function test_la_liste_publique_exige_une_authentification(): void
    {
        $this->getJson('/api/offres')->assertUnauthorized();
    }

    public function test_un_candidat_peut_consulter_une_offre_ouverte(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/offres/{$offre->id_offre}")
            ->assertOk()
            ->assertJsonPath('offre.id_offre', $offre->id_offre);
    }

    public function test_un_candidat_ne_peut_pas_consulter_une_offre_fermee(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->fermee()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/offres/{$offre->id_offre}")
            ->assertForbidden();
    }

    public function test_le_recruteur_consulte_sa_propre_offre_fermee(): void
    {
        $offre = OffreEmploi::factory()->publieePar($this->recruteur, $this->departement)->fermee()->create();

        $this->actingAs($this->utilisateurRecruteur, 'sanctum')
            ->getJson("/api/offres/{$offre->id_offre}")
            ->assertOk();
    }

    public function test_une_offre_inexistante_retourne_404(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/offres/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Ressource introuvable.');
    }
}
