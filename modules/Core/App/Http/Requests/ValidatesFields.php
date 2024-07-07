<?php
 

namespace Modules\Core\App\Http\Requests;

use Closure;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;

/** @mixin \Modules\Core\App\Http\Requests\ResourceRequest */
trait ValidatesFields
{
    /**
     * Get the available fields for the request.
     */
    abstract public function getFields(): FieldsCollection;

    /**
     * Perform validation to the fields.
     */
    public function performValidation(): void
    {
        $this->validate($this->rules(), $this->messages(), $this->attributes());
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return $this->getFields()->reject(fn ($field) => empty($field->label))
            ->mapWithKeys(function (Field $field) {
                return [$field->requestAttribute() => html_entity_decode(strip_tags(trim($field->label)))];
            })->all();
    }

    /**
     * Get the error messages for the current resource request.
     */
    public function messages(): array
    {
        return $this->getFields()->map(function (Field $field) {
            return $field->prepareValidationMessages();
        })->filter()->collapse()->all();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return $this->formatRules($this->getFields()->mapWithKeys(function (Field $field) {
            return $field->getRules();
        })->all());
    }

    /**
     * Format the rules for the resource.
     */
    public function formatRules(array $rules): array
    {
        // We will get all of the Closure based rules and will create a custom closure
        // that will pass the request instance as 4th parameter.
        foreach ($rules as $key => $attributeRules) {
            if (is_string($attributeRules)) {
                $rules[$key] = $attributeRules = [$attributeRules];
            }

            foreach ($attributeRules as $ruleKey => $rule) {
                if ($rule instanceof Closure) {
                    $rules[$key][$ruleKey] = function (string $attribute, mixed $value, Closure $fail) use ($rule) {
                        $rule($attribute, $value, $fail, $this);
                    };
                }
            }
        }

        return $rules;
    }
}
