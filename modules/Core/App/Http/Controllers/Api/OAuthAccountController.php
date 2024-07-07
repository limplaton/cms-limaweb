<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Resources\OAuthAccountResource;
use Modules\Core\App\Models\OAuthAccount;

class OAuthAccountController extends ApiController
{
    /**
     * Get the user connected OAuth accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $collection = OAuthAccountResource::collection(
            OAuthAccount::where('user_id', $request->user()->id)->get()
        );

        return $this->response($collection);
    }

    /**
     * Display the specified oauth account.
     */
    public function show(OAuthAccount $account): JsonResponse
    {
        $this->authorize('view', $account);

        return $this->response(new OAuthAccountResource($account));
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(OAuthAccount $account): JsonResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
