<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Table\Column;

trait DisplaysOnIndex
{
    /**
     * @var callable[]
     */
    public array $tapIndexColumnCallbacks = [];

    /**
     * @var null|callable
     */
    public $indexColumnCallback;

    /**
     * Provide the column used for index
     */
    public function indexColumn(): ?Column
    {
        return new Column($this->attribute, $this->label);
    }

    /**
     * Add custom index column resolver callback
     */
    public function swapIndexColumn(callable $callback): static
    {
        $this->indexColumnCallback = $callback;

        return $this;
    }

    /**
     * Tap the index column
     */
    public function tapIndexColumn(callable $callback): static
    {
        $this->tapIndexColumnCallbacks[] = $callback;

        return $this;
    }

    /**
     * Resolve the index column
     *
     * @return \Modules\Core\App\Table\Column|null
     */
    public function resolveIndexColumn()
    {
        /** @var \Modules\Core\App\Table\Column */
        $column = is_callable($this->indexColumnCallback) ?
                  call_user_func_array($this->indexColumnCallback, [$this]) :
                  $this->indexColumn();

        if (is_null($column)) {
            return null;
        }

        $column->help($this->helpText);
        $column->hidden(! $this->showOnIndex);
        $column->setField($this);

        foreach ($this->tapIndexColumnCallbacks as $callback) {
            tap($column, $callback);
        }

        return $column;
    }
}
