<?php
 

namespace Modules\Core\App\Actions;

use Illuminate\Support\Collection;

abstract class DestroyableAction extends Action
{
    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        foreach ($models as $model) {
            $model->delete();
        }
    }

    /**
     * Action name
     */
    public function name(): string
    {
        return __('core::app.delete');
    }
}
