<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Contracts\Fields\UniqueableCustomfield;

class Url extends Field implements Customfieldable, UniqueableCustomfield
{
    /**
     * Field component.
     */
    public static $component = 'url-field';

    /**
     * Initialize new Url instance.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->provideSampleValueUsing(fn () => config('app.url'));
    }

    /**
     * Include "https" in front of the URL.
     */
    public function https(bool $value = true)
    {
        $this->withMeta(['https' => $value]);

        return $this;
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->string($fieldId)->nullable();
    }
}
