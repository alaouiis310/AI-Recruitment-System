<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if (! $user->compteActif()) {
            return response()->json([
                'message' => 'Ce compte est ' . $user->etat_compte->libelle() . '.',
            ], 403);
        }

        if (! in_array($user->role->value, $roles, true)) {
            return response()->json([
                'message' => 'Accès refusé : cette ressource est réservée aux rôles suivants : '
                    . implode(', ', $roles) . '.',
            ], 403);
        }

        return $next($request);
    }
}
