<?php

use App\Enums\Recommandation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Table ANALYSE_IA du MLD (RG37 à RG42). */
    public function up(): void
    {
        Schema::create('analyses_ia', function (Blueprint $table) {
            $table->id('id_analyse');

            // Score global et ses trois composantes, toutes calculées en PHP par ScoringService (RG40).
            $table->decimal('score_matching', 5, 2);
            $table->decimal('score_competence', 5, 2)->default(0);
            $table->decimal('score_experience', 5, 2)->default(0);
            $table->decimal('score_diplome', 5, 2)->default(0);

            // Compétences exigées que le candidat ne déclare pas (RG41).
            $table->json('competences_manquantes')->nullable();

            // Seules ces deux colonnes peuvent provenir du modèle (RG42).
            $table->text('resume_cv')->nullable();
            $table->enum('recommandation', Recommandation::valeurs());

            $table->date('date_analyse');

            // Une seule analyse par candidature (RG38).
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

    /** Les scores sont compris entre 0 et 100 (RG39). */
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
