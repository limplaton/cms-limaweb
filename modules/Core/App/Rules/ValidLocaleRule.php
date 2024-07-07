<?php
 

namespace Modules\Core\App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Core\App\Facades\Innoclapps;

class ValidLocaleRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, Innoclapps::locales())) {
            $fail('This locale does not exists.');
        }
    }
}
