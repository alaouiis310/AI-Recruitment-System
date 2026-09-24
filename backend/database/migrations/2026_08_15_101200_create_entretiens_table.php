<?php

use App\Enums\ModeEntretien;
use App\Enums\ResultatEntretien;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Table ENTRETIEN du MLD (RG34, RG35, RG36). */
    public function up(): void
    {
        Schema::create('entretiens', function (Blueprint $table) {
            $table->id('id_entretien');

            // Date, heure, mode et résultat (RG36).
            $table->date('date');
            $table->time('heure');
            $table->enum('mode', ModeEntretien::valeurs());
            $table->string('lien_si_online', 255)->nullable();
            $table->text('commentaire')->nullable();
            $table->enum('resultat', ResultatEntretien::valeurs())
                ->default(ResultatEntretien::EnAttente->value);

            // Chaque entretien porte sur une seule candidature (RG35).
            $table->foreignId('id_candidature')
                ->constrained('candidatures', 'id_candidature')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['id_candidature', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entretiens');
    }
};
