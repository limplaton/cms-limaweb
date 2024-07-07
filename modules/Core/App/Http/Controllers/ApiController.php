<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Request;

abstract class ApiController extends Controller
{
    /**
     * General API Response.
     */
    public function response($data = [], $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        // https://stackoverflow.com/questions/57604784/laravel-resource-collection-paginate-json-response-error - for paginated collections
        if ($data instanceof AnonymousResourceCollection) {
            $data = $data->toResponse(Request::instance())->getData();
        }

        return response()->json($data, $status, $headers, $options);
    }
}
