<?php
 

namespace Modules\MailClient\App\Fields;

use Modules\Core\App\Fields\Field;

class MailEditor extends Field
{
    /**
     * Field component.
     */
    public static $component = 'mail-editor-field';

    /**
     * Resolve the field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    public function resolve($model)
    {
        return clean(parent::resolve($model));
    }
}
