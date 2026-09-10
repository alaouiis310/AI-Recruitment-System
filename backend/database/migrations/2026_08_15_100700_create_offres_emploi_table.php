<?php

use App\Enums\NiveauEtude;
use App\Enums\StatutOffre;
use App\Enums\TypeContrat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table OFFRE_EMPLOI du MLD — RG10 à RG18.
     */
    public function up(): void
    {
        Schema::create('offres_emploi', function (Blueprint $table) {
            $table->id('id_offre');

            // RG15 — titre, description, type de contrat, localisation, statut.
            $table->string('titre', 150);
            $table->text('description');
            $table->enum('type_contrat', TypeContrat::valeurs());
            $table->string('localisation', 100);
            $table->decimal('salaire', 10, 2)->nullable();
            $table->decimal('experience_min', 4, 1)->default(0);
            $table->enum('niveau_etude', NiveauEtude::valeurs())->nullable();

            // RG16/RG17 — dates de publication et d'expiration.
            $table->date('date_publication');
            $table->date('date_expiration')->nullable();

            // RG18 — ouverte, fermée ou suspendue.
            $table->enum('statut', StatutOffre::valeurs())->default(StatutOffre::Ouverte->value);

            // RG13 — chaque offre est publiée par un seul recruteur.
            $table->foreignId('id_recruteur')
                ->constrained('recruteurs', 'id_recruteur')
                ->cascadeOnDelete();

            // RG11 — chaque offre relève d'un seul département.
            $table->foreignId('id_departement')
                ->constrained('departements', 'id_departement')
                ->restrictOnDelete();

            $table->timestamps();

            // Index des filtres de la liste candidat.
            $table->index(['statut', 'date_expiration']);
            $table->index('localisation');
            $table->index('type_contrat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres_emploi');
    }
};
