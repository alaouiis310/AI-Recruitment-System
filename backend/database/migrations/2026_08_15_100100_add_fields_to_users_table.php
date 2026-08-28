<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('prenom', 100)->nullable()->after('name');
            $table->enum('role', ['administrateur', 'recruteur', 'candidat'])
                ->default('candidat')->after('email');
            $table->enum('etat_compte', ['actif', 'suspendu', 'desactive'])
                ->default('actif')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'role', 'etat_compte']);
        });
    }
};
