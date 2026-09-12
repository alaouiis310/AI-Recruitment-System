<?php

namespace App\Services;

use App\Models\NotificationApp;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Notifications applicatives (RG44).
 *
 * Point d'entrée unique pour notifier un utilisateur : les autres modules
 * appellent `envoyer()` plutôt que d'écrire directement en base.
 */
class NotificationService
{
    /** RG44 — dépose une notification pour un utilisateur. */
    public function envoyer(User $destinataire, string $message): NotificationApp
    {
        return NotificationApp::create([
            'id_user'    => $destinataire->id,
            'message'    => $message,
            'date_envoi' => now(),
            'lu'         => false,
        ]);
    }

    /** Notifications du destinataire, non lues en tête. */
    public function lister(User $destinataire, array $filtres): LengthAwarePaginator
    {
        return $destinataire->notifications()
            ->when($filtres['non_lues'] ?? null, fn ($q) => $q->nonLues())
            ->ordreAffichage()
            ->paginate(perPage: $filtres['per_page'] ?? 15)
            ->withQueryString();
    }

    public function compterNonLues(User $destinataire): int
    {
        return $destinataire->notifications()->nonLues()->count();
    }

    public function marquerLue(NotificationApp $notification): NotificationApp
    {
        $notification->update(['lu' => true]);

        return $notification->fresh();
    }

    /** Marque toutes les notifications du destinataire comme lues. */
    public function marquerToutesLues(User $destinataire): int
    {
        return $destinataire->notifications()->nonLues()->update(['lu' => true]);
    }
}
