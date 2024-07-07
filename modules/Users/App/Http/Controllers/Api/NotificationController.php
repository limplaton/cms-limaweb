<?php
 

namespace Modules\Users\App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\App\Http\Controllers\ApiController;

class NotificationController extends ApiController
{
    /**
     * List current user notifications.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->response(
            $request->user()->notifications()->paginate($request->integer('per_page', 15))
        );
    }

    /**
     * Retrieve current user notification.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        return $this->response(
            $request->user()->notifications()->findOrFail($id)
        );
    }

    /**
     * Set all notifications for current user as read.
     */
    public function update(Request $request, ?string $id = ''): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->when($id !== '', fn (Builder $query) => $query->where('id', $id))
            ->update(['read_at' => now()]);

        return $this->response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete current user notification
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->findOrFail($id)
            ->delete();

        return $this->response('', Response::HTTP_NO_CONTENT);
    }
}
