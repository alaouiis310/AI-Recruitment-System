<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Levée lorsqu'une ressource ne peut pas être supprimée sans détruire des données qui en dépendent. */
class SuppressionImpossibleException extends Exception
{
    /** @param  array<string, array<int, string>>  $erreurs */
    public function __construct(string $message, private readonly array $erreurs = [])
    {
        parent::__construct($message);
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return response()->json(array_filter([
            'message' => $this->getMessage(),
            'errors'  => $this->erreurs ?: null,
        ]), 409);
    }
}
