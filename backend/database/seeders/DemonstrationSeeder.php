<?php

namespace Database\Seeders;

use App\Enums\EtatCompte;
use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use App\Enums\RoleUtilisateur;
use App\Enums\StatutCandidature;
use App\Models\Candidat;
use App\Models\Candidature;
use App\Models\Competence;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\Entretien;
use App\Models\NotificationApp;
use App\Models\OffreEmploi;
use App\Models\Recruteur;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Jeu de démonstration — données réalistes pour la présentation de l'application.
 *
 * Complète les seeders de base : quatre entreprises supplémentaires, treize
 * offres, seize candidats pourvus d'un CV PDF et quarante et une candidatures
 * étalées sur cinq mois, de sorte que les tableaux de bord et le graphique
 * d'évolution aient de la matière.
 *
 * Aucun score n'est écrit ici : AnalyseIaSeeder, exécuté ensuite, calcule
 * chaque score avec le vrai ScoringService (RG40). Toutes les dates sont
 * relatives au jour de l'exécution.
 */
class DemonstrationSeeder extends Seeder
{
    private const MOT_DE_PASSE = 'Password123';

    /** @var array<string, int> titre d'offre => id_offre */
    private array $offres = [];

    public function run(): void
    {
        $this->entreprises();
        $this->offres();
        $this->candidats();
        $this->candidatures();
        $this->entretiens();
        $this->notifications();

        $this->command?->info('  Jeu de démonstration : '.count($this->offres).' offres, '
            .Candidature::count().' candidatures au total.');
    }

    // -----------------------------------------------------------------
    // Entreprises, départements et recruteurs (RG5 à RG9)
    // -----------------------------------------------------------------

    private function entreprises(): void
    {
        foreach (DonneesDemonstration::entreprises() as $e) {
            $entreprise = Entreprise::create($e['entreprise']);

            foreach ($e['departements'] as $nom => $description) {
                Departement::create([
                    'id_entreprise' => $entreprise->id_entreprise,
                    'nom' => $nom,
                    'description' => $description,
                ]);
            }

            [$prenom, $nom, $email, $poste, $telephone] = $e['recruteur'];
            $user = $this->utilisateur($prenom, $nom, $email, RoleUtilisateur::Recruteur);
            Recruteur::create([
                'id_user' => $user->id,
                'id_entreprise' => $entreprise->id_entreprise,
                'telephone' => $telephone,
                'poste' => $poste,
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Offres et compétences requises (RG10 à RG21)
    // -----------------------------------------------------------------

    private function offres(): void
    {
        foreach (DonneesDemonstration::offres() as $o) {
            $recruteur = User::where('email', $o['recruteur'])->firstOrFail()->recruteur;
            $departement = Departement::where('id_entreprise', $recruteur->id_entreprise)
                ->where('nom', $o['departement'])->firstOrFail();

            $offre = OffreEmploi::create([
                'titre' => $o['titre'],
                'description' => $o['description'],
                'type_contrat' => $o['contrat'],
                'localisation' => $o['ville'],
                'salaire' => $o['salaire'],
                'experience_min' => $o['experience'],
                'niveau_etude' => $o['niveau'],
                'date_publication' => now()->subDays($o['publiee_il_y_a'])->toDateString(),
                'date_expiration' => now()->addDays($o['expire_dans'])->toDateString(),
                'statut' => $o['statut'],
                'id_recruteur' => $recruteur->id_recruteur,
                'id_departement' => $departement->id_departement,
            ]);

            $offre->competences()->sync($this->pivot($o['competences'], 'niveau_requis', 'importance'));
            $this->offres[$o['titre']] = $offre->id_offre;
        }
    }

    // -----------------------------------------------------------------
    // Candidats, compétences déclarées et CV (RG22 à RG26)
    // -----------------------------------------------------------------

    private function candidats(): void
    {
        foreach (DonneesDemonstration::candidats() as $c) {
            $user = $this->utilisateur($c['prenom'], $c['nom'], $c['email'], RoleUtilisateur::Candidat);
            $candidat = Candidat::create([
                'id_user' => $user->id,
                'telephone' => $c['telephone'],
                'adresse' => $c['adresse'],
                'date_naissance' => $c['naissance'],
                'diplome' => $c['diplome'],
                'github' => 'https://github.com/'.Str::slug($c['prenom'].'-'.$c['nom']),
                'linkedin' => 'https://linkedin.com/in/'.Str::slug($c['prenom'].'-'.$c['nom']),
                'experience_totale' => $c['experience'],
            ]);

            $candidat->competences()->sync($this->pivot($c['competences'], 'niveau', 'annees_experience'));
            $this->deposerCv($candidat, $c);
        }

        // Les trois comptes de base reçoivent eux aussi un CV consultable.
        foreach (['candidat@airs.ma', 'candidat2@airs.ma', 'candidat3@airs.ma'] as $email) {
            $candidat = User::where('email', $email)->first()?->candidat;
            if ($candidat) {
                $this->deposerCv($candidat->load('user', 'competences'), null);
            }
        }
    }

    // -----------------------------------------------------------------
    // Candidatures (RG27 à RG33), étalées sur cinq mois
    // -----------------------------------------------------------------

    private function candidatures(): void
    {
        foreach (DonneesDemonstration::candidatures() as [$email, $titre, $ilYA, $statut, $decisionIlYA, $commentaire]) {
            $candidat = User::where('email', $email)->firstOrFail()->candidat;
            $offre = OffreEmploi::findOrFail($this->idOffre($titre));
            $statut = StatutCandidature::from($statut);

            Candidature::create([
                'id_candidat' => $candidat->id_candidat,
                'id_offre' => $offre->id_offre,
                'date_candidature' => now()->subDays($ilYA)->toDateString(),
                'lettre_motivation' => DonneesDemonstration::lettre($candidat->user->prenom, $offre->titre, (float) $candidat->experience_totale),
                'statut' => $statut,
                // RG32 : seule une décision définitive est datée.
                'date_decision' => $statut->estDefinitif() ? now()->subDays($decisionIlYA)->toDateString() : null,
                'commentaire_recruteur' => $commentaire,
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Entretiens (RG34 à RG36)
    // -----------------------------------------------------------------

    private function entretiens(): void
    {
        foreach (DonneesDemonstration::entretiens() as [$email, $titre, $dansJours, $heure, $mode, $resultat, $commentaire]) {
            $candidature = $this->candidature($email, $titre);
            Entretien::create([
                'id_candidature' => $candidature->id_candidature,
                'date' => now()->addDays($dansJours)->toDateString(),
                'heure' => $heure,
                'mode' => ModeEntretien::from($mode),
                'lien_si_online' => $mode === 'visio' ? 'https://meet.example.ma/airs-'.$candidature->id_candidature : null,
                'commentaire' => $commentaire,
                'resultat' => ResultatEntretien::from($resultat),
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Notifications (RG44)
    // -----------------------------------------------------------------

    private function notifications(): void
    {
        // Décisions et présélections : le candidat est prévenu.
        $candidatures = Candidature::with(['candidat.user', 'offre'])
            ->whereIn('statut', ['acceptee', 'refusee', 'preselectionnee'])
            ->get();

        foreach ($candidatures as $c) {
            $message = match ($c->statut) {
                StatutCandidature::Acceptee => "Bonne nouvelle : votre candidature « {$c->offre->titre} » a été acceptée.",
                StatutCandidature::Refusee => "Votre candidature « {$c->offre->titre} » n'a pas été retenue.",
                default => "Votre candidature « {$c->offre->titre} » a été présélectionnée.",
            };
            $quand = $c->date_decision ?? $c->date_candidature->copy()->addDays(3);
            NotificationApp::create([
                'id_user' => $c->candidat->id_user,
                'message' => $message,
                'date_envoi' => $quand->copy()->setTime(9, 30),
                // Les plus anciennes ont déjà été lues.
                'lu' => $quand->lt(now()->subDays(10)),
            ]);
        }

        // Le recruteur de démonstration est prévenu des candidatures récentes.
        $recruteur = User::where('email', 'recruteur@airs.ma')->first();
        if (! $recruteur) {
            return;
        }

        $recues = Candidature::with(['candidat.user', 'offre'])
            ->duRecruteur($recruteur->recruteur->id_recruteur)
            ->where('date_candidature', '>=', now()->subDays(15)->toDateString())
            ->get();

        foreach ($recues as $c) {
            NotificationApp::create([
                'id_user' => $recruteur->id,
                'message' => "Nouvelle candidature de {$c->candidat->user->nomComplet()} pour « {$c->offre->titre} ».",
                'date_envoi' => $c->date_candidature->copy()->setTime(11, 0),
                'lu' => $c->date_candidature->lt(now()->subDays(7)),
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Utilitaires
    // -----------------------------------------------------------------

    private function utilisateur(string $prenom, string $nom, string $email, RoleUtilisateur $role): User
    {
        return User::create([
            'name' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => self::MOT_DE_PASSE,
            'role' => $role,
            'etat_compte' => EtatCompte::Actif,
        ]);
    }

    /**
     * Convertit ['PHP' => ['avance', 3]] en attributs de pivot, les compétences
     * étant désignées par leur nom dans le référentiel partagé (RG20, RG25).
     */
    private function pivot(array $liste, string $cle1, string $cle2): array
    {
        return Competence::whereIn('nom', array_keys($liste))->get()
            ->mapWithKeys(fn (Competence $c) => [$c->id_competence => [
                $cle1 => $liste[$c->nom][0],
                $cle2 => $liste[$c->nom][1],
            ]])
            ->all();
    }

    private function idOffre(string $titre): int
    {
        return $this->offres[$titre] ?? OffreEmploi::where('titre', $titre)->firstOrFail()->id_offre;
    }

    private function candidature(string $email, string $titre): Candidature
    {
        $candidat = User::where('email', $email)->firstOrFail()->candidat;

        return Candidature::where('id_candidat', $candidat->id_candidat)
            ->where('id_offre', $this->idOffre($titre))
            ->firstOrFail();
    }

    /** RG22 — CV PDF généré depuis le profil, déposé sur le disque public. */
    private function deposerCv(Candidat $candidat, ?array $donnees): void
    {
        $user = $candidat->user;
        $pdf = (new GenerateurCvPdf)
            ->titre($user->nomComplet())
            ->ligne($donnees['accroche'] ?? ($candidat->diplome ?? ''), 12)
            ->ligne(implode('  |  ', array_filter([$user->email, $candidat->telephone, $candidat->adresse])))
            ->section('Formation')
            ->ligne($candidat->diplome ?? 'Non renseignée')
            ->section('Expérience')
            ->ligne($donnees['parcours'] ?? sprintf("%s année(s) d'expérience professionnelle.", $candidat->experience_totale));

        $pdf->section('Compétences');
        foreach ($candidat->competences()->get() as $competence) {
            $pdf->ligne(sprintf('- %s : %s, %s an(s)', $competence->nom, $competence->pivot->niveau, $competence->pivot->annees_experience));
        }

        $chemin = 'cv/demo-'.Str::slug($user->nomComplet()).'.pdf';
        Storage::disk('public')->put($chemin, $pdf->contenu());
        $candidat->update(['cv_pdf' => $chemin]);
    }
}
