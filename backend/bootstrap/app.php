<?php

use App\Http\Middleware\VerifierCompteActif;
use App\Http\Middleware\VerifierRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'         => VerifierRole::class,
            'compte.actif' => VerifierCompteActif::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Non authentifié. Fournissez un jeton valide.',
                ], 401);
            }
        });

        // Les rappels sont typés sur l'exception déjà préparée par le noyau :
        // une AuthorizationException est devenue AccessDeniedHttpException, et
        // une ModelNotFoundException une NotFoundHttpException.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => "Accès refusé : vous n'êtes pas autorisé à effectuer cette action.",
                ], 403);
            }
        });

        // Le message par défaut divulgue le nom complet de la classe du modèle.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Ressource introuvable.',
                ], 404);
            }
        });
    })->create();
