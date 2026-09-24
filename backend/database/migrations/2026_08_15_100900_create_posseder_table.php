<?php

use App\Enums\NiveauCompetence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pivot POSSEDER du MLD (RG24, RG25, RG26). */
    public function up(): void
    {
        Schema::create('posseder', function (Blueprint $table) {
            $table->foreignId('id_candidat')
                ->constrained('candidats', 'id_candidat')
                ->cascadeOnDelete();

            $table->foreignId('id_competence')
                ->constrained('competences', 'id_competence')
                ->cascadeOnDelete();

            // Niveau de maîtrise et ancienneté déclarés par le candidat (RG26).
            $table->enum('niveau', NiveauCompetence::valeurs());
            $table->decimal('annees_experience', 4, 1)->default(0);

            $table->primary(['id_candidat', 'id_competence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posseder');
    }
};
