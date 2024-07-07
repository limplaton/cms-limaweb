<?php
 

namespace Modules\Core\App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\Core\App\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\App\Contracts\Resources\AcceptsUniqueCustomFields;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\UniqueRule;

class CustomFieldRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'resource_name' => [
                Rule::requiredIfMethodPost($this),
                'string',
                Rule::in(
                    Innoclapps::registeredResources()
                        ->whereInstanceOf(AcceptsCustomFields::class)
                        ->map(fn (Resource $resource) => $resource->name())
                ),
            ],

            'label' => ['required', StringRule::make()],

            'field_type' => [Rule::requiredIfMethodPost($this), Rule::in(Fields::customFieldsTypes())],

            'is_unique' => [
                'sometimes',  'nullable', 'boolean', Rule::prohibitedIf(
                    function () {
                        if (! $this->isCreation()) {
                            return false;
                        }

                        if (! Innoclapps::registeredResources()->first(
                            fn ($resource) => $resource->name() === $this->resource_name
                        ) instanceof AcceptsUniqueCustomFields) {
                            return true;
                        }

                        return ! in_array($this->field_type, Fields::getUniqueableCustomFieldsTypes());
                    }
                ),
            ],

            'field_id' => $this->getFieldIdRules(),

            'options' => ['nullable', 'array', function (string $attribute, mixed $value, Closure $fail) {
                $customField = ! $this->isCreation() ?
                    CustomField::find($this->route('custom_field')) :
                    null;

                $fieldType = $this->isCreation() ? $this->field_type : $customField->field_type;

                $optionNames = array_filter(Arr::pluck($value, 'name'));

                if (! in_array($fieldType, Fields::getOptionableCustomFieldsTypes()) && count($optionNames) > 0) {
                    $fail('core::fields.validation.refuses_options')->translate();
                }

                if (in_array($fieldType, Fields::getOptionableCustomFieldsTypes()) && empty($optionNames)) {
                    $fail('core::fields.validation.requires_options')->translate();
                }

                foreach ($optionNames as $name) {
                    if (str_contains($name, ',')) {
                        $fail(__('core::fields.validation.option_coma'))->translate();
                    }
                }

                if (count($optionNames) !== count(array_unique($optionNames))) {
                    $fail('All field options must be unique.');
                }
            }],

            'options.*.data' => 'nullable|array',
        ];
    }

    /**
     * Get the field_id attribute rules.
     */
    protected function getFieldIdRules(): array
    {
        // Not rules as the field_id can't be updated once the field is created
        if (! $this->isCreation()) {
            return [];
        }

        $prefix = config('fields.custom_fields.prefix');

        return [
            'bail',
            'required',
            'min:'.(3 + strlen($prefix)), // the user is required to enter at least 3 characters
            'max:64', // https://dev.mysql.com/doc/refman/5.7/en/identifier-length.html
            'regex:/^[a-z_]+$/',
            'starts_with:'.$prefix,
            UniqueRule::make(CustomField::class, 'custom_field')->where('resource_name', $this->resource_name),
            function (string $attribute, mixed $value, Closure $fail) {
                $resource = Innoclapps::resourceByName($this->resource_name);

                // First we will check if database column exists
                if (Schema::hasColumn(app($resource::$model)->getTable(), $value)) {
                    return $fail('core::fields.validation.exist')->translate();
                }

                // Finally, we will check if there is actually field with the same attribute/id
                // defined in the resource fields, includes default fields and custom fields
                if ($resource->getFields($this)->find($value)) {
                    return $fail('core::fields.validation.exist')->translate();
                }
            },
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'field_type.in' => __('core::fields.validation.field_type_invalid'),
            'field_id.regex' => __('core::fields.validation.field_id_invalid'),
            'field_id.unique' => __('core::fields.validation.exist'),
        ];
    }

    /**
     * Check whether is custom field creation request
     */
    protected function isCreation(): bool
    {
        return $this->isMethod('POST');
    }
}
