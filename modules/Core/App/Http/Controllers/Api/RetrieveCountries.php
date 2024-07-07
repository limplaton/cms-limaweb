<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Resources\CountryResource;
use Modules\Core\App\Models\Country;

class RetrieveCountries extends ApiController
{
    /**
     * Get a list of all the application countries in storage.
     */
    public function __invoke(): JsonResponse
    {
        return $this->response(
            CountryResource::collection(Country::get())
        );
    }
}
