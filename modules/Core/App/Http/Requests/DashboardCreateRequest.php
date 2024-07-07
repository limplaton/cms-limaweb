<?php
 

namespace Modules\Core\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\App\Rules\StringRule;

class DashboardCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->baseRules();
    }

    /**
     * Get the base validation rules that apply to the request.
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', StringRule::make()],
            'cards.*.key' => 'sometimes|required',
            'cards.*.order' => 'sometimes|numeric',
            'cards.*.enabled' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ];
    }
}
