<?php
 

namespace Modules\Users\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\App\Rules\StringRule;

class TeamRequest extends FormRequest
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
            'description' => ['nullable', StringRule::make()],
            'user_id' => 'required|numeric',
            'members' => 'array',
            'members.*' => 'numeric',
        ];
    }
}
