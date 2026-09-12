<?php

namespace App\Policies;

use App\Models\NotificationApp;
use App\Models\User;

/**
 * RG44 — une notification n'appartient qu'à son destinataire.
 */
class NotificationAppPolicy
{
    public function view(User $user, NotificationApp $notification): bool
    {
        return $user->estAdministrateur() || $notification->id_user === $user->id;
    }

    public function update(User $user, NotificationApp $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function delete(User $user, NotificationApp $notification): bool
    {
        return $this->view($user, $notification);
    }
}
