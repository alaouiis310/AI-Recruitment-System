<?php

use App\Enums\CategorieCompetence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table COMPETENCE du MLD — RG19, RG20, RG24, RG25.
     */
    public function up(): void
    {
        Schema::create('competences', function (Blueprint $table) {
            $table->id('id_competence');

            // Référentiel partagé : le nom identifie la compétence (RG20, RG25).
            $table->string('nom', 100)->unique();
            $table->enum('categorie', CategorieCompetence::valeurs());
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('categorie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competences');
    }
};
