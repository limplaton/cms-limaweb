<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Models\Model;

trait Deleteable
{
    /**
     * Specify a callback to be called when deleting the field related model.
     */
    public function deleteUsing(callable $callback): static
    {
        $this->deleteCallback = $callback;

        return $this;
    }

    /**
     * Handle the field model deletition.
     */
    public function delete(Model $model): void
    {
        if (is_callable($this->deleteCallback)) {
            call_user_func_array($this->deleteCallback, [$model]);
        }
    }
}
