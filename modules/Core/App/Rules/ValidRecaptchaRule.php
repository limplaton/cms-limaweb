<?php
 

namespace Modules\Core\App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Modules\Core\App\Facades\ReCaptcha;

class ValidRecaptchaRule implements ValidationRule
{
    /**
     * The endpoint to verify recaptcha
     */
    protected string $verifyEndpoint = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Http::asForm()->post($this->verifyEndpoint, [
            'secret' => ReCaptcha::getSecretKey(),
            'response' => $value,
        ])['success']) {
            $fail('validation.recaptcha')->translate();
        }
    }
}
