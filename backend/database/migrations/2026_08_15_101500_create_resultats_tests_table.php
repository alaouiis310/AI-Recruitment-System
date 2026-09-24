<?php

use App\Enums\StatutResultatTest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table RESULTAT_TEST du MLD — RG45.
     */
    public function up(): void
    {
        Schema::create('resultats_tests', function (Blueprint $table) {
            $table->id('id_resultat');

            $table->date('date_passage')->nullable();
            $table->unsignedSmallInteger('score_obtenu')->nullable();
            $table->enum('statut', StatutResultatTest::valeurs())
                ->default(StatutResultatTest::Envoye->value);
            $table->text('commentaire')->nullable();

            $table->foreignId('id_candidature')
                ->constrained('candidatures', 'id_candidature')
                ->cascadeOnDelete();

            $table->foreignId('id_test')
                ->constrained('tests_techniques', 'id_test')
                ->cascadeOnDelete();

            $table->timestamps();

            // Un test n'est envoyé qu'une fois par candidature.
            $table->unique(['id_candidature', 'id_test']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultats_tests');
    }
};
