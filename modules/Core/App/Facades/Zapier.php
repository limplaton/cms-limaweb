<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\Zapier\Zapier as BaseZapier;

/**
 * @method static void processQueue()
 * @method static array modelEvents()
 * @method static static queue(string $action, array|int $records, \Modules\Core\App\Resource\Resource $resource)
 *
 * @see \Modules\Core\App\Zapier\Zapier
 */
class Zapier extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseZapier::class;
    }
}
