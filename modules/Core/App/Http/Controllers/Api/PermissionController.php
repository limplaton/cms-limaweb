<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Facades\Permissions;
use Modules\Core\App\Http\Controllers\ApiController;

class PermissionController extends ApiController
{
    /**
     * Get all registered application permissions.
     */
    public function index(): JsonResponse
    {
        Permissions::createMissing();

        return $this->response([
            'grouped' => Permissions::groups(),
            'all' => Permissions::all(),
        ]);
    }
}
