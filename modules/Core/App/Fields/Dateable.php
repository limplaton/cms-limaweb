<?php
 

namespace Modules\Core\App\Fields;

trait Dateable
{
    /**
     * Resolve the field value for export.
     *
     * @param  \Modules\Core\App\Models\Model  $model
     * @return string
     */
    public function resolveForExport($model)
    {
        if (is_callable($this->exportCallback)) {
            return call_user_func_array($this->exportCallback, [$model, $this->resolve($model), $this->attribute]);
        }

        return $model->{$this->attribute};
    }

    /**
     * Mark the field as clearable
     */
    public function clearable(): static
    {
        $this->withMeta(['attributes' => ['clearable' => true]]);

        return $this;
    }
}
