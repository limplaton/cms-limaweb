<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Core\App\Facades\Tools;
use Modules\Core\App\Http\Controllers\ApiController;

class ExecuteTool extends ApiController
{
    /**
     * Execute the given tool.
     */
    public function __invoke(string $tool): JsonResponse
    {
        // Tool execute flag

        abort_unless(Tools::has($tool), 404);

        $data = Tools::execute($tool);

        return $this->response(
            $data,
            is_string($data) && empty($data) || is_null($data) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK
        );
    }
}
