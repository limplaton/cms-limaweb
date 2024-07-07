<?php
 

namespace Modules\Core\App\Rules;

class UniqueResourceRule extends UniqueRule
{
    /**
     * Indicates whether to exclude the unique validation from import.
     */
    public bool $skipOnImport = false;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $modelName, string|int|null $ignore = 'resourceId', ?string $column = 'NULL')
    {
        parent::__construct($modelName, $ignore, $column);
    }

    /**
     * Set whether the exclude this validation rule from import.
     */
    public function skipOnImport(bool $value): static
    {
        $this->skipOnImport = $value;

        return $this;
    }
}
