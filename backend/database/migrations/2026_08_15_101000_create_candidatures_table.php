<?php

use App\Enums\StatutCandidature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table CANDIDATURE du MLD — RG27 à RG33, RG43.
     */
    public function up(): void
    {
        Schema::create('candidatures', function (Blueprint $table) {
            $table->id('id_candidature');

            // RG33 — date de dépôt de la candidature.
            $table->date('date_candidature');
            $table->text('lettre_motivation')->nullable();

            // RG32 — cycle de vie de la candidature.
            $table->enum('statut', StatutCandidature::valeurs())
                ->default(StatutCandidature::EnAttente->value);

            // RG43 — score retenu pour le classement, alimenté par l'analyse
            // du module 6 (RG40) ; nul tant qu'aucune analyse n'a abouti.
            $table->decimal('score_final', 5, 2)->nullable();

            $table->date('date_decision')->nullable();
            $table->text('commentaire_recruteur')->nullable();

            // RG28 — une candidature appartient à un seul candidat.
            $table->foreignId('id_candidat')
                ->constrained('candidats', 'id_candidat')
                ->cascadeOnDelete();

            // RG29 — une candidature concerne une seule offre.
            $table->foreignId('id_offre')
                ->constrained('offres_emploi', 'id_offre')
                ->cascadeOnDelete();

            $table->timestamps();

            // RG31 — une seule candidature par candidat et par offre.
            $table->unique(['id_candidat', 'id_offre']);

            // RG43 — classement des candidatures d'une offre par score.
            $table->index(['id_offre', 'score_final']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures');
    }
};
