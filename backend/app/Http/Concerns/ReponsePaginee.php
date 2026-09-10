<?php

namespace App\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ReponsePaginee
{
    /**
     * Enveloppe standard des collections paginées : la ressource est exposée
     * sous son nom français au pluriel, accompagnée du bloc de pagination.
     *
     * @param  class-string<\Illuminate\Http\Resources\Json\JsonResource>  $resource
     */
    protected function paginee(LengthAwarePaginator $page, string $cle, string $resource): JsonResponse
    {
        return response()->json([
            $cle         => $resource::collection($page->items()),
            'pagination' => [
                'page_courante' => $page->currentPage(),
                'par_page'      => $page->perPage(),
                'total'         => $page->total(),
                'derniere_page' => $page->lastPage(),
            ],
        ]);
    }
}
