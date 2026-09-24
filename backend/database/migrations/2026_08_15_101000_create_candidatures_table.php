<?php

use App\Enums\StatutCandidature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Table CANDIDATURE du MLD (RG27 à RG33, RG43). */
    public function up(): void
    {
        Schema::create('candidatures', function (Blueprint $table) {
            $table->id('id_candidature');

            // Date de dépôt de la candidature (RG33).
            $table->date('date_candidature');
            $table->text('lettre_motivation')->nullable();

            // Cycle de vie de la candidature (RG32).
            $table->enum('statut', StatutCandidature::valeurs())
                ->default(StatutCandidature::EnAttente->value);

            // Score retenu pour le classement, alimenté par l'analyse du module 6 (RG40) (RG43).
            $table->decimal('score_final', 5, 2)->nullable();

            $table->date('date_decision')->nullable();
            $table->text('commentaire_recruteur')->nullable();

            // Une candidature appartient à un seul candidat (RG28).
            $table->foreignId('id_candidat')
                ->constrained('candidats', 'id_candidat')
                ->cascadeOnDelete();

            // Une candidature concerne une seule offre (RG29).
            $table->foreignId('id_offre')
                ->constrained('offres_emploi', 'id_offre')
                ->cascadeOnDelete();

            $table->timestamps();

            // Une seule candidature par candidat et par offre (RG31).
            $table->unique(['id_candidat', 'id_offre']);

            // Classement des candidatures d'une offre par score (RG43).
            $table->index(['id_offre', 'score_final']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures');
    }
};
