<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table NOTIFICATION du MLD — RG44.
     *
     * Nommée `notifications_app` et non `notifications` : Laravel réserve ce
     * dernier nom pour son propre système de notifications.
     */
    public function up(): void
    {
        Schema::create('notifications_app', function (Blueprint $table) {
            $table->id('id_notification');

            $table->string('message', 500);
            $table->timestamp('date_envoi');
            $table->boolean('lu')->default(false);

            // RG44 — un utilisateur peut recevoir plusieurs notifications.
            $table->foreignId('id_user')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            // Liste du destinataire : non lues d'abord, plus récentes en tête.
            $table->index(['id_user', 'lu', 'date_envoi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_app');
    }
};
