<?php

use App\Enums\NiveauCompetence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot POSSEDER du MLD — RG24, RG25, RG26.
     *
     * Table d'association, pas une entité : clé primaire composite et aucun
     * modèle Eloquent dédié. L'accès se fait par belongsToMany()->withPivot().
     */
    public function up(): void
    {
        Schema::create('posseder', function (Blueprint $table) {
            $table->foreignId('id_candidat')
                ->constrained('candidats', 'id_candidat')
                ->cascadeOnDelete();

            $table->foreignId('id_competence')
                ->constrained('competences', 'id_competence')
                ->cascadeOnDelete();

            // RG26 — niveau de maîtrise et ancienneté déclarés par le candidat.
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
