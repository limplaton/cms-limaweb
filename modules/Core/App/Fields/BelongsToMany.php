<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Table\BelongsToManyColumn;

/**
 * Currently used only for INDEX, not working on forms or other views.
 */
class BelongsToMany extends Optionable
{
    /**
     * Field component.
     */
    public static $component = 'select-multiple-field';

    /**
     * Field relationship name
     */
    public string $belongsToManyRelationship;

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

        $this->belongsToManyRelationship = $attribute;

        $this->fillUsing(function () {
        });
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): BelongsToManyColumn
    {
        return new BelongsToManyColumn(
            $this->belongsToManyRelationship,
            $this->labelKey,
            $this->label
        );
    }
}
