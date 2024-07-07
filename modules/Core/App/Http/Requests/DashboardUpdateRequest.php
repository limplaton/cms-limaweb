<?php
 

namespace Modules\Core\App\Http\Requests;

use Modules\Core\App\Rules\StringRule;

class DashboardUpdateRequest extends DashboardCreateRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('dashboard'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseRules(),
            ['name' => ['sometimes', 'required', StringRule::make()]]
        );
    }
}
