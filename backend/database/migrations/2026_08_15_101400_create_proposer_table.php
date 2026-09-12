<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot PROPOSER du MLD — RG45.
     *
     * Table d'association, pas une entité : clé primaire composite et aucun
     * modèle Eloquent dédié. L'accès se fait par belongsToMany().
     */
    public function up(): void
    {
        Schema::create('proposer', function (Blueprint $table) {
            $table->foreignId('id_offre')
                ->constrained('offres_emploi', 'id_offre')
                ->cascadeOnDelete();

            $table->foreignId('id_test')
                ->constrained('tests_techniques', 'id_test')
                ->cascadeOnDelete();

            $table->primary(['id_offre', 'id_test']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposer');
    }
};
