<?php
 

namespace Modules\Core\App\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\App\MailableTemplate\MailableTemplatesManager;

/**
 * @method static static register(string|array $mailable)
 * @method static array get()
 * @method static bool shouldSeed()
 * @method static static seed()
 * @method static static seedForLocale(string $locale, string $mailable = null)
 *
 * @see \Modules\Core\App\MailableTemplate\MailableTemplatesManager
 */
class MailableTemplates extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return MailableTemplatesManager::class;
    }
}
