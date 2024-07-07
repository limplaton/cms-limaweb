<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Common\Changelog\Logging as BaseLogging;

/**
 * @method static static disable()
 * @method static static enable()
 * @method static void disabled(\Closure $callback)
 * @method static \Modules\Core\App\Models\Changelog onModel(\Modules\Core\App\Models\Model $model, array $attributes)
 *
 * @see \Modules\Core\App\Common\Changelog\Logging
 */
class ChangeLogger extends Facade
{
    /**
     * Indicates the model log name
     */
    const MODEL_LOG_NAME = 'model';

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseLogging::class;
    }
}
