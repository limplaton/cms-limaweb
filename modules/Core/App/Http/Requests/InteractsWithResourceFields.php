<?php
 

namespace Modules\Core\App\Http\Requests;

use Illuminate\Support\Collection;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Resource\Resource;

/** @mixin \Modules\Core\App\Http\Requests\ResourceRequest */
trait InteractsWithResourceFields
{
    use RunsValidationCallbacks,
        ValidatesFields {
            ValidatesFields::messages as messagesFromFields;
        }

    protected ?FieldsCollection $fields = null;

    /**
     * Validate the class instance.
     *
     * NOTE: We do not validate when resolved, the "performValidation" method must be called to perform validation.
     *
     * @return void
     */
    public function validateResolved()
    {
    }

    /**
     * Validate the request for the resource.
     */
    public function performValidation(): void
    {
        parent::validateResolved();
    }

    /**
     * Get the available fields for the request.
     */
    public function getFields(): FieldsCollection
    {
        return $this->fields ?? (method_exists($this, 'fields') ? $this->fields() : new FieldsCollection);
    }

    /**
     * Find field from the available fields for the request by attribute.
     */
    public function findField(string $attribute): ?Field
    {
        return $this->getFields()->find($attribute);
    }

    /**
     * Set the fields for the request.
     */
    public function setFields(?FieldsCollection $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get fields instances from the available request keys.
     *
     * @return Collection<\Modules\Core\App\Fields\Field>
     */
    public function toFields()
    {
        $fields = $this->getFields();

        return $this
            ->collect()
            ->keys()
            ->map(fn (string $attribute) => $fields->findByRequestAttribute($attribute))
            ->filter();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return $this->formatRules(array_merge_recursive(
            $this->resource()->rules($this),
            $this->isCreateRequest() ?
                    $this->resource()->createRules($this) :
                    $this->resource()->updateRules($this),
            $this->getFields()->mapWithKeys(function (Field $field) {
                return $this->isCreateRequest() ? $field->getCreationRules() : $field->getUpdateRules();
            })->all()
        ));
    }

    /**
     * Get the error messages for the current resource request.
     */
    public function messages(): array
    {
        return array_merge($this->messagesFromFields(), $this->resource()->validationMessages());
    }

    /**
     * Get the available associateables for the request.
     */
    public function associateables(): array
    {
        $associations = $this->resource()->associateableResources();
        $fields = $this->getFields();

        return $this->collect()->filter(function ($value, $attribute) use ($associations, $fields) {
            // First, we will check if the attribute name is the special attribute "associations"
            if ($attribute === 'associations') {
                return true;
            }

            // Next, we will check if the attribute exists as field in the
            // in this case, we will filter this key as not associateable
            // as the saving should be handled directly by the field.
            if ($fields->findByRequestAttribute($attribute)) {
                return false;
            }

            // Finally, we will check if the attribute exists as available associateable
            // resource for the current resource, if exists, we will check if the resource is associateable
            // This helps to provide the associations on resources without fields defined
            return $associations->first(
                fn (Resource $resource, string $relation) => $relation === $attribute
            )?->isAssociateable();
        })->all();
    }
}
