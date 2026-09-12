<?php

namespace App\Services;

use App\Enums\StatutResultatTest;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use App\Models\ResultatTest;
use App\Models\TestTechnique;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Tests techniques et leurs passages (RG45).
 */
class TestTechniqueService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /** Catalogue des tests disponibles. */
    public function lister(array $filtres): LengthAwarePaginator
    {
        return TestTechnique::query()
            ->when($filtres['recherche'] ?? null, fn ($q, string $t) => $q->where('titre', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $t).'%'))
            ->orderBy('titre')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    public function creer(array $donnees): TestTechnique
    {
        return TestTechnique::create($donnees);
    }

    public function modifier(TestTechnique $test, array $donnees): TestTechnique
    {
        $test->update($donnees);

        return $test->fresh();
    }

    public function supprimer(TestTechnique $test): void
    {
        $test->delete();
    }

    /** RG45 — rattache un ensemble de tests à une offre (pivot proposer). */
    public function rattacherAOffre(OffreEmploi $offre, array $idsTests): OffreEmploi
    {
        $offre->tests()->sync($idsTests);

        return $offre->fresh('tests');
    }

    /**
     * RG44/RG45 — envoie un test au candidat d'une candidature et l'en avise.
     *
     * Deux écritures — le résultat et la notification — d'où la transaction.
     */
    public function envoyerAuCandidat(Candidature $candidature, TestTechnique $test): ResultatTest
    {
        return DB::transaction(function () use ($candidature, $test) {
            $resultat = ResultatTest::updateOrCreate(
                [
                    'id_candidature' => $candidature->id_candidature,
                    'id_test'        => $test->id_test,
                ],
                ['statut' => StatutResultatTest::Envoye],
            );

            $this->notifications->envoyer(
                $candidature->candidat->user,
                sprintf(
                    'Un test technique « %s » vous a été envoyé pour l\'offre « %s ».',
                    $test->titre,
                    $candidature->offre->titre,
                ),
            );

            return $resultat->fresh(['test']);
        });
    }

    /** RG45 — enregistre le score obtenu et clôt le passage. */
    public function enregistrerScore(ResultatTest $resultat, array $donnees): ResultatTest
    {
        $resultat->update([
            'score_obtenu' => $donnees['score_obtenu'],
            'commentaire'  => $donnees['commentaire'] ?? $resultat->commentaire,
            'statut'       => StatutResultatTest::Termine,
            'date_passage' => $donnees['date_passage'] ?? now()->toDateString(),
        ]);

        return $resultat->fresh(['test', 'candidature']);
    }

    /** RG14 — passages relevant des offres publiées par un recruteur. */
    public function listerDuRecruteur(int $idRecruteur, array $filtres): LengthAwarePaginator
    {
        return ResultatTest::query()
            ->duRecruteur($idRecruteur)
            ->with(['test', 'candidature.candidat.user', 'candidature.offre'])
            ->when($filtres['statut'] ?? null, fn ($q, string $v) => $q->where('statut', $v))
            ->orderByDesc('updated_at')
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }
}
