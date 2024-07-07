<?php
 

namespace Modules\Users\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Users\App\Http\Requests\PasswordRequest;
use Modules\Users\App\Http\Requests\ProfileRequest;
use Modules\Users\App\Http\Resources\UserResource;
use Modules\Users\App\Models\User;
use Modules\Users\App\Services\UserService;

class ProfileController extends ApiController
{
    /**
     * Get user.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->response(new UserResource(
            User::withCommon()->find($request->user()->id)
        ));
    }

    /**
     * Update profile.
     */
    public function update(ProfileRequest $request, UserService $service): JsonResponse
    {
        // Profile update flag

        $user = $service->update(
            $request->user(),
            $request->except(['super_admin', 'access_api']),
        );

        return $this->response(new UserResource(
            User::withCommon()->find($user->id)
        ));
    }

    /**
     * Change password.
     */
    public function password(PasswordRequest $request, UserService $service): JsonResponse
    {
        // Profile update password flag
        $service->update(
            $request->user(),
            ['password' => $request->get('password')],
        );

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
