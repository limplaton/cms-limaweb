<?php
 

namespace Modules\Translator\App\Contracts;

interface TranslationLoader
{
    /**
     * Returns all translations for the given locale and group.
     */
    public function loadTranslations(string $locale, string $group, string $namespace): array;
}
