<?php
 

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PreventPasswordReset;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Modules\Core\App\Facades\ReCaptcha;
use Modules\Core\App\Rules\ValidRecaptchaRule;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
    use SendsPasswordResetEmails;

    /**
     * Initialize new ForgotPasswordController instance.
     */
    public function __construct()
    {
        $this->middleware(PreventPasswordReset::class);
    }

    /**
     * Validate the email for the given request.
     */
    protected function validateEmail(Request $request): void
    {
        $request->validate([
            'email' => 'required|string|email',
            ...ReCaptcha::shouldShow() ? ['g-recaptcha-response' => ['required', new ValidRecaptchaRule]] : [],
        ]);
    }
}
