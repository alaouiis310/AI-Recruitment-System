<?php

use App\Enums\ImportanceCompetence;
use App\Enums\NiveauCompetence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pivot REQUERIR du MLD (RG19, RG20, RG21). */
    public function up(): void
    {
        Schema::create('requerir', function (Blueprint $table) {
            $table->foreignId('id_offre')
                ->constrained('offres_emploi', 'id_offre')
                ->cascadeOnDelete();

            $table->foreignId('id_competence')
                ->constrained('competences', 'id_competence')
                ->cascadeOnDelete();

            // Niveau minimal exigé et importance de la compétence (RG21).
            $table->enum('niveau_requis', NiveauCompetence::valeurs());
            $table->enum('importance', ImportanceCompetence::valeurs())
                ->default(ImportanceCompetence::Importante->value);

            $table->primary(['id_offre', 'id_competence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requerir');
    }
};
