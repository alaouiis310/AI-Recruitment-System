<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruteurs', function (Blueprint $table) {
            $table->id('id_recruteur');
            $table->string('telephone', 20)->nullable();
            $table->string('poste', 100)->nullable();

            $table->foreignId('id_user')->unique()
                ->constrained('users')->cascadeOnDelete();

            $table->foreignId('id_entreprise')
                ->constrained('entreprises', 'id_entreprise')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruteurs');
    }
};
