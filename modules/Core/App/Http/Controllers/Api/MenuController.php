<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Facades\Menu;
use Modules\Core\App\Http\Controllers\ApiController;

class MenuController extends ApiController
{
    /**
     * Get the application menu metrics.
     */
    public function metrics(): JsonResponse
    {
        return $this->response(Menu::metrics());
    }
}
