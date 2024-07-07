<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Workflow\Workflows;

class WorkflowTriggers extends ApiController
{
    /**
     * Get the available triggers.
     */
    public function __invoke(): JsonResponse
    {
        return $this->response(Workflows::triggersInstance());
    }
}
