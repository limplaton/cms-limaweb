<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Models\ZapierHook;

class ZapierHookController extends ApiController
{
    /**
     * Subscribe to a hook.
     */
    public function store(string $resourceName, string $action, Request $request): JsonResponse
    {
        $hook = new ZapierHook([
            'hook' => $request->targetUrl,
            'resource_name' => $resourceName,
            'action' => $action,
            'user_id' => $request->user()->id,
            // Needs further testing, previously the zapId was only numeric
            // but now includes subscriptions:zapId
            'zap_id' => str_contains($request->zapId, 'subscription:') ?
                explode('subscription:', $request->zapId)[1] :
                $request->zapId,
            'data' => $request->data,
        ]);

        $hook->save();

        return $this->response($hook, JsonResponse::HTTP_CREATED);
    }

    /**
     * Unsubscribe from hook.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        ZapierHook::where('user_id', $request->user()->getKey())->findOrFail($id)->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
