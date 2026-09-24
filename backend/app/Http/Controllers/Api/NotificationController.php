<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ReponsePaginee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\ListerNotificationsRequest;
use App\Http\Resources\NotificationAppResource;
use App\Models\NotificationApp;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifications du compte authentifié (RG44).
 *
 * Ces points d'accès portent toujours sur les notifications du destinataire
 * connecté : il n'y a pas d'identifiant d'utilisateur dans les URL.
 */
class NotificationController extends Controller
{
    use ReponsePaginee;

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(ListerNotificationsRequest $request): JsonResponse
    {
        $reponse = $this->paginee(
            $this->notifications->lister($request->user(), $request->validated()),
            'notifications',
            NotificationAppResource::class,
        );

        // Le compteur alimente la pastille de l'interface.
        $donnees = $reponse->getData(true);
        $donnees['non_lues'] = $this->notifications->compterNonLues($request->user());

        return response()->json($donnees);
    }

    public function marquerLue(Request $request, NotificationApp $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        return response()->json([
            'message'      => 'Notification marquée comme lue.',
            'notification' => new NotificationAppResource(
                $this->notifications->marquerLue($notification)
            ),
        ]);
    }

    public function marquerToutesLues(Request $request): JsonResponse
    {
        $nombre = $this->notifications->marquerToutesLues($request->user());

        return response()->json([
            'message'  => "{$nombre} notification(s) marquée(s) comme lue(s).",
            'non_lues' => 0,
        ]);
    }
}
