<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Facades\Timezone;
use Modules\Core\App\Http\Controllers\ApiController;

class RetrieveTimezones extends ApiController
{
    /**
     * Get a list of all of available timezones.
     */
    public function __invoke(): JsonResponse
    {
        return $this->response(Timezone::all());
    }
}
