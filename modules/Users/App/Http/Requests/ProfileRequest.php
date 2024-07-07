<?php
 

namespace Modules\Users\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\UniqueRule;
use Modules\Core\App\Rules\ValidLocaleRule;
use Modules\Users\App\Models\User;

class ProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', StringRule::make()],
            'email' => [
                'required',
                StringRule::make(),
                'email',
                UniqueRule::make(User::class, $this->user()->id),
            ],
            'time_format' => ['required', 'string', Rule::in(config('core.time_formats'))],
            'date_format' => ['required', 'string', Rule::in(config('core.date_formats'))],
            'locale' => ['required', 'string', new ValidLocaleRule],
            'timezone' => ['required', 'string', 'timezone:all'],
        ];
    }
}
