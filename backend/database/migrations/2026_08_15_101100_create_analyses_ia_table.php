<?php

use App\Enums\Recommandation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table ANALYSE_IA du MLD — RG37 à RG42.
     */
    public function up(): void
    {
        Schema::create('analyses_ia', function (Blueprint $table) {
            $table->id('id_analyse');

            // RG40 — score global et ses trois composantes, toutes calculées
            // en PHP par ScoringService : aucune ne provient du modèle.
            $table->decimal('score_matching', 5, 2);
            $table->decimal('score_competence', 5, 2)->default(0);
            $table->decimal('score_experience', 5, 2)->default(0);
            $table->decimal('score_diplome', 5, 2)->default(0);

            // RG41 — compétences exigées que le candidat ne déclare pas.
            $table->json('competences_manquantes')->nullable();

            // Seules ces deux colonnes peuvent provenir du modèle (RG42).
            $table->text('resume_cv')->nullable();
            $table->enum('recommandation', Recommandation::valeurs());

            $table->date('date_analyse');

            // RG38 — une seule analyse par candidature.
            $table->foreignId('id_candidature')->unique()
                ->constrained('candidatures', 'id_candidature')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        $this->ajouterContraintesDeScore();
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses_ia');
    }

    /**
     * RG39 — les scores sont compris entre 0 et 100.
     *
     * SQLite, utilisé par la suite de tests, ne sait pas ajouter une
     * contrainte à une table existante : elle n'y est donc pas posée. Le
     * calcul borne les valeurs de son côté et un test unitaire le vérifie.
     */
    private function ajouterContraintesDeScore(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['matching', 'competence', 'experience', 'diplome'] as $volet) {
            DB::statement(
                "ALTER TABLE analyses_ia ADD CONSTRAINT chk_score_{$volet} ".
                "CHECK (score_{$volet} BETWEEN 0 AND 100)"
            );
        }
    }
};
