<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table TEST_TECHNIQUE du MLD — RG45.
     */
    public function up(): void
    {
        Schema::create('tests_techniques', function (Blueprint $table) {
            $table->id('id_test');
            $table->string('titre', 150);
            $table->text('description')->nullable();

            // Durée en minutes.
            $table->unsignedSmallInteger('duree');
            $table->unsignedSmallInteger('score_max')->default(100);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests_techniques');
    }
};
