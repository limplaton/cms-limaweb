<?php
 

namespace Modules\Users\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\ValidLocaleRule;
use Modules\Users\App\Models\User;
use Modules\Users\App\Models\UserInvitation;
use Modules\Users\App\Services\UserService;

class UserInvitationAcceptController extends Controller
{
    /**
     * Show to invitation accept form.
     */
    public function show(string $token, Request $request): View
    {
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

        $this->authorizeInvitation($invitation);

        return view('users::invitations.show', compact('invitation'));
    }

    /**
     * Accept the invitation and create account.
     */
    public function accept(string $token, Request $request, UserService $service): void
    {
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

        $this->authorizeInvitation($invitation);

        $data = $request->validate([
            'name' => ['required', StringRule::make()],
            'email' => [StringRule::make(), 'email', 'unique:'.(new User())->getTable()],
            'password' => 'required|confirmed|min:6',
            'timezone' => 'required|timezone:all',
            'locale' => ['nullable', new ValidLocaleRule],
            'date_format' => ['required', Rule::in(config('core.date_formats'))],
            'time_format' => ['required', Rule::in(config('core.time_formats'))],
        ]);

        $user = $service->create(new User, array_merge($data, [
            'super_admin' => $invitation->super_admin,
            'access_api' => $invitation->access_api,
            'roles' => $invitation->roles,
            'teams' => $invitation->teams,
            'email' => $invitation->email,
            'notifications' => collect(Notifications::preferences())->mapWithKeys(function ($setting) {
                return [$setting['key'] => $setting['channels']->mapWithKeys(function ($channel) {
                    return [$channel => true];
                })->all()];
            })->all(),
        ]));

        Auth::loginUsingId($user->id);

        $invitation->delete();
    }

    /**
     * Authorize the given invitation.
     */
    protected function authorizeInvitation(UserInvitation $invitation): void
    {
        abort_if($invitation->created_at->diffInDays() > config('users.invitation.expires_after'), 404);
    }
}
