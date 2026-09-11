<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table DEPARTEMENT du MLD — RG8, RG9.
     */
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id('id_departement');
            $table->string('nom', 100);
            $table->text('description')->nullable();

            // RG9 — chaque département appartient à une seule entreprise.
            $table->foreignId('id_entreprise')
                ->constrained('entreprises', 'id_entreprise')
                ->cascadeOnDelete();

            $table->timestamps();

            // RG8 — le nom d'un département est unique au sein d'une entreprise,
            // mais deux entreprises peuvent avoir un département homonyme.
            $table->unique(['id_entreprise', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};
