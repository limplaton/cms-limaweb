<?php
 

namespace Modules\MailClient\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\UniqueRule;
use Modules\MailClient\App\Models\PredefinedMailTemplate;

class PredefinedMailTemplateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', StringRule::make(), UniqueRule::make(PredefinedMailTemplate::class, 'template')],
            'subject' => ['required', StringRule::make()],
            'body' => ['required', 'string'],
            'is_shared' => ['required', 'boolean'],
        ];
    }
}
