<?php
 

namespace Modules\Core\App\Updater\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UpdaterException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json($this->getMessage(), $this->getCode());
        }

        return response($this->getMessage(), $this->getCode());
    }
}
