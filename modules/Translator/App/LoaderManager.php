<?php
 

namespace Modules\Translator\App;

use Illuminate\Translation\FileLoader;
use Modules\Translator\App\Contracts\TranslationLoader;

class LoaderManager extends FileLoader
{
    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     */
    public function load($locale, $group, $namespace = null): array
    {
        $original = parent::load($locale, $group, $namespace);

        // JSON translations are not supported
        if ($group === '*') {
            return $original;
        }

        return array_replace_recursive(
            $original,
            app(TranslationLoader::class)->loadTranslations($locale, $group, $namespace)
        );
    }
}
