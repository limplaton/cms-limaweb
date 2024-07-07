<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Support\LogReader;
use Modules\Core\App\SystemInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemController extends ApiController
{
    /**
     * Get the system info
     */
    public function info(Request $request): JsonResponse
    {
        // System info flag

        return $this->response(new SystemInfo($request));
    }

    /**
     * Download the system info
     */
    public function downloadInfo(Request $request): BinaryFileResponse
    {
        // System info download flag

        return Excel::download(new SystemInfo($request), 'system-info.xlsx');
    }

    /**
     * Get the application/Laravel logs
     */
    public function logs(Request $request): JsonResponse
    {
        // System logs flag

        return $this->response(
            new LogReader(['date' => $request->date])
        );
    }
}
