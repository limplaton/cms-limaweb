<?php
 

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Core\App\Facades\ReCaptcha;
use Modules\Core\App\Rules\ValidRecaptchaRule;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Validate the user login request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            ...ReCaptcha::shouldShow() ? ['g-recaptcha-response' => ['required', new ValidRecaptchaRule]] : [],
        ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  mixed  $user
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function authenticated(Request $request, $user)
    {
        $response = redirect()->intended($this->redirectPath());

        if (! $request->wantsJson()) {
            return $response;
        }

        $redirectTo = Str::endsWith($response->getTargetUrl(), ['.js', '.css', '.ico', '.png', '.jpg', '.jpeg']) ?
            $this->redirectPath() :
            $response->getTargetUrl();

        return new JsonResponse(['redirect_path' => $redirectTo], 200);
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        // Get the current stored locale.
        $currentLocale = $request->session()->pull('locale');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Store again the locale as the session is flushed via the "invalidate" method.
        $request->session()->put('locale', $currentLocale);

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect('/');
    }
}
