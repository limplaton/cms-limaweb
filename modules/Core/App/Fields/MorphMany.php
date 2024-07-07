<?php
 

namespace Modules\Core\App\Fields;

abstract class MorphMany extends Field
{
    /**
     * Field relationship name.
     */
    public string $morphManyRelationship;

    /**
     * Indicates if the field is excluded from index query.
     */
    public bool $isExcludedFromIndexQuery = true;

    /**
     * Indicates if the field is searchable.
     */
    protected bool $searchable = false;

    /**
     * Initialize new MorphMany instance class.
     *
     * @param  string  $attribute
     * @param  string|null  $label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this->morphManyRelationship = $attribute;

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
            'morphManyRelationship' => $this->morphManyRelationship,
        ]);
    }
}
