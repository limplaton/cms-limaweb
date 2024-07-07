<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Akaunting\Money\Currency;
use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;

class RetrieveCurrencies extends ApiController
{
    /**
     * Get the application available currencies.
     */
    public function __invoke(): JsonResponse
    {
        return $this->response(Currency::getCurrencies());
    }
}
