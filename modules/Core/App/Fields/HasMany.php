<?php
 

namespace Modules\Core\App\Fields;

abstract class HasMany extends Field
{
    /**
     * Field relationship name
     */
    public string $hasManyRelationship;

    /**
     * Indicates if the field is excluded from index query.
     */
    public bool $isExcludedFromIndexQuery = true;

    /**
     * Indicates if the field is searchable.
     */
    protected bool $searchable = false;

    /**
     * Initialize new HasMany instance class
     *
     * @param  string  $attribute
     * @param  string|null  $label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this->hasManyRelationship = $attribute;

        $this->fillUsing(function () {
        });
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     */
    public function mailableTemplatePlaceholder($model)
    {
        return null;
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'hasManyRelationship' => $this->hasManyRelationship,
        ]);
    }
}
