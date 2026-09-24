<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidats', function (Blueprint $table) {
            $table->id('id_candidat');
            $table->string('telephone', 20)->nullable();
            $table->string('telephone2', 20)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('diplome', 150)->nullable();
            $table->string('cv_pdf', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('github', 255)->nullable();
            $table->string('linkedin', 255)->nullable();
            $table->decimal('experience_totale', 4, 1)->default(0);

            $table->foreignId('id_user')->unique()
                ->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidats');
    }
};
