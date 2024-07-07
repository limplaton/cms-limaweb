<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Table\Column;

class Textarea extends Field implements Customfieldable
{
    /**
     * Field component.
     */
    public static $component = 'textarea-field';

    /**
     * The inline edit popover width (medium|large).
     */
    public string $inlineEditPanelWidth = 'large';

    /**
     * Textarea rows attribute
     */
    public function rows(string|int $rows): static
    {
        $this->withMeta(['attributes' => ['rows' => $rows]]);

        return $this;
    }

    /**
     * Provide the column used for index view
     */
    public function indexColumn(): Column
    {
        $column = parent::indexColumn();

        $column->newlineable = true;
        $column->width('400px');

        return $column;
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\App\Models\Model|null  $model
     * @return \Modules\Core\App\Common\Placeholders\GenericPlaceholder
     */
    public function mailableTemplatePlaceholder($model)
    {
        $placeholder = parent::mailableTemplatePlaceholder($model);

        $placeholder->newlineable = true;

        return $placeholder;
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->text($fieldId)->nullable();
    }
}
