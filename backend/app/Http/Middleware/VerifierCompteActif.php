<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierCompteActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->compteActif()) {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Ce compte est ' . $user->etat_compte->libelle() . '. Contactez un administrateur.',
            ], 403);
        }

        return $next($request);
    }
}
