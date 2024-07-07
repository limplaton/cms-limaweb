<?php
 

namespace Modules\Core\App\Actions;

class ActionFields
{
    /**
     * Create new instance of action request fields
     */
    public function __construct(protected array $fields)
    {
    }

    /**
     * Get all of the available fields.
     */
    public function all(): array
    {
        return $this->fields;
    }

    /**
     * Get field
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->fields[$name] ?? null;
    }
}
