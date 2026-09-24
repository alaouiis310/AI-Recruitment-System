<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Table DEPARTEMENT du MLD (RG8, RG9). */
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id('id_departement');
            $table->string('nom', 100);
            $table->text('description')->nullable();

            // Chaque département appartient à une seule entreprise (RG9).
            $table->foreignId('id_entreprise')
                ->constrained('entreprises', 'id_entreprise')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['id_entreprise', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};
