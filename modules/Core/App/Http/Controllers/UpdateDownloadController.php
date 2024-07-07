<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\App\Updater\Patcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UpdateDownloadController extends Controller
{
    /**
     * Download the given patch
     */
    public function downloadPatch(string $token, ?string $purchaseKey = null): BinaryFileResponse
    {
        // Download patch flag

        if ($purchaseKey) {
            settings(['purchase_key' => $purchaseKey]);
        }

        $patcher = app(Patcher::class);

        return $patcher->download($token);
    }
}
