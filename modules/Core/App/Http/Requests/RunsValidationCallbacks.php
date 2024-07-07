<?php
 

namespace Modules\Core\App\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;

/** @mixin \Modules\Core\App\Http\Requests\ResourceRequest */
trait RunsValidationCallbacks
{
    /**
     * Get the available fields for the request.
     */
    abstract public function getFields(): FieldsCollection;

    /**
     * Run the fields validation callbacks.
     */
    public function runValidationCallbacks(Validator $validator): static
    {
        $fields = $this->fieldsForValidationCallback();

        $this->merge(
            $fields->mapWithKeys(function (Field $field) use ($validator) {
                return [$field->requestAttribute() => $this->runValidationCallback($field, $validator)];
            })->all()
        );

        $fields->each(function (Field $field) {
            if (method_exists($field, 'afterValidationCallback')) {
                $field->afterValidationCallback(
                    $this->fieldInput($field),
                    $this,
                );
            }
        });

        return $this;
    }

    /**
     * Run validation callback for the given field.
     */
    protected function runValidationCallback(Field $field, Validator $validator): mixed
    {
        return call_user_func_array(
            $field->validationCallback,
            [$this->fieldInput($field), $this, $validator]
        );
    }

    /**
     * Get the fields applicable for validation callback.
     */
    protected function fieldsForValidationCallback(): FieldsCollection
    {
        return $this->getFields()->reject(function ($field) {
            return is_null($field->validationCallback) || $this->missing($field->requestAttribute());
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->setOriginal($this->all());

        Field::setRequest($this);

        $this->ensureInputIsAuthorized();

        $validator = $this->getValidatorInstance();

        // Laravel sets the validator data once the validator instance is created,
        // since we are not creating our own validator instance, we need to re-set the data
        // with the new (possibly) modified request data from the validation callbacks.
        $validator->setData($this->runValidationCallbacks($validator)->all());
    }

    /**
     * Set the authorized input attributes for the request.
     *
     * We will remove any non-authorized attributes based on all available fields,
     * it will make sure that even hidden or non-authorized fields for the user
     * cannot be injected in the request input.
     */
    protected function ensureInputIsAuthorized(): void
    {
        $fields = $this->allFields();

        $this->replace($this->collect()->filter(function ($value, $attribute) use ($fields) {
            return with($fields->findByRequestAttribute($attribute), function ($field) {
                if (! $field) {
                    return true;
                }

                return $field->authorizedToSee() && ! $field->isReadOnly();
            });
        })->all());
    }

    /**
     * Get the field input from the request.
     */
    protected function fieldInput(Field $field)
    {
        return $field->attributeFromRequest($this, $field->requestAttribute());
    }
}
