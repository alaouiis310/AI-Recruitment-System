<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationAppResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_notification' => $this->id_notification,
            'message'         => $this->message,
            'date_envoi'      => $this->date_envoi?->toIso8601String(),
            'lu'              => $this->lu,
            'id_user'         => $this->id_user,
        ];
    }
}
