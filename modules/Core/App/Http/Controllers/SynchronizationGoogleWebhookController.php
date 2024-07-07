<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\App\Models\Synchronization;

class SynchronizationGoogleWebhookController extends Controller
{
    /**
     *  Handle the webhook request.
     */
    public function handle(Request $request): void
    {
        if ($request->header('x-goog-resource-state') !== 'exists') {
            return;
        }

        $synchronization = Synchronization::where('resource_id', $request->header('x-goog-resource-id'))
            ->findOrFail($request->header('x-goog-channel-id'));

        $synchronization->ping();
    }
}
