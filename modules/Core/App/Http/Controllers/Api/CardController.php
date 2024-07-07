<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Facades\Cards;
use Modules\Core\App\Http\Controllers\ApiController;

class CardController extends ApiController
{
    /**
     * Get cards that are intended to be shown on dashboards.
     */
    public function forDashboards(): JsonResponse
    {
        return $this->response(Cards::resolveForDashboard());
    }

    /**
     * Get the available cards for a given resource.
     */
    public function index(string $resourceName): JsonResponse
    {
        return $this->response(Cards::resolve($resourceName));
    }

    /**
     * Get card by given uri key.
     */
    public function show(string $card): JsonResponse
    {
        return $this->response(Cards::registered()->first(function ($item) use ($card) {
            return $item->uriKey() === $card;
        })->authorizeOrFail());
    }
}
