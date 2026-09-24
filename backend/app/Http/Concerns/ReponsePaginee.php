<?php

namespace App\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ReponsePaginee
{
    /**
     * Enveloppe standard des collections paginées.
     *
     * @param  class-string<JsonResource>  $resource
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
