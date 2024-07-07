<?php
 

namespace Modules\Translator\App;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Modules\Translator\App\Contracts\TranslationLoader;
use Symfony\Component\Finder\SplFileInfo;

class Translator
{
    protected string $fallbackLocale;

    public function __construct()
    {
        $this->fallbackLocale = trans()->getFallback();
    }

    /**
     * Set the fallback locale.
     */
    public function setFallbackLocale(string $locale): static
    {
        $this->fallbackLocale = $locale;

        return $this;
    }

    /**
     * Save the given translations in storage.
     */
    public function save(string $locale, string $group, array $translations, ?string $namespace = null): void
    {
        $path = app(TranslationLoader::class)->getOverridePath();

        $DS = DIRECTORY_SEPARATOR;

        $storagePath = $path.$DS.$locale;

        if (! is_null($namespace)) {
            $storagePath .= $DS.'_'.$namespace;
        }

        File::ensureDirectoryExists($storagePath);

        File::put(
            $storagePath.$DS.$group.'.php',
            "<?php\n\nreturn ".var_export($translations, true).';'.\PHP_EOL
        );

        static::generateJsonLanguageFile();
    }

    /**
     * Get the original unmodified translations.
     */
    public function source(string $locale): array
    {
        return [
            'groups' => $this->retrieveGroupsSource($locale),
            'namespaces' => $this->retrieveNamespacesSource($locale),
        ];
    }

    /**
     * Get the current translations (with override applied)
     */
    public function current(string $locale): array
    {
        return [
            'groups' => $this->getCurrentGroups($locale),
            'namespaces' => $this->getCurrentNamespacesWithGroups($locale),
        ];
    }

    /**
     * Retrieve the groups translations for source.
     */
    protected function retrieveGroupsSource(string $locale): array
    {
        $path = lang_path($this->fallbackLocale);

        return $this->groups($path)->mapWithKeys(function (TranslationGroup $group) use ($locale) {
            return [$group->name() => $this->getGroupSourceTranslations($group, lang_path(), $locale)];
        })->when($locale !== $this->fallbackLocale, function ($collection) {
            return $this->mergeMissingSourceKeys($collection, lang_path());
        })->all();
    }

    /**
     * Retrieve namespaces groups translations for source.
     */
    protected function retrieveNamespacesSource(string $locale): array
    {
        return $this->getNamespaces()->mapWithKeys(
            fn ($path, $namespace) => [$namespace => $this->retrieveGroupsTranslationsFromPath($path, $locale)]
        )->filter(fn ($groups) => count($groups) > 0)->all();
    }

    /**
     * Get the current groups translations.
     */
    protected function getCurrentGroups(string $locale): array
    {
        $path = lang_path($this->fallbackLocale);

        return $this->groups($path)->mapWithKeys(
            fn (TranslationGroup $group) => [$group->name() => $group->translations($locale)]
        )->all();
    }

    /**
     * Get the current namespaces with groups translations.
     */
    protected function getCurrentNamespacesWithGroups(string $locale): array
    {
        return $this->getNamespaces()->mapWithKeys(function ($path, $namespace) use ($locale) {
            $localePath = $path.DIRECTORY_SEPARATOR.$this->fallbackLocale;

            return [
                $namespace => collect($this->groups($localePath))
                    ->mapWithKeys(function (TranslationGroup $group) use ($namespace, $locale) {
                        return [$group->name() => $group->translations($locale, $namespace)];
                    })->all(),
            ];
        })->filter(fn ($groups) => count($groups) > 0)->all();
    }

    /**
     * Retrieve groups translations from the given groups location path
     */
    protected function retrieveGroupsTranslationsFromPath(string $langPath, string $locale): array
    {
        $localePath = $langPath.DIRECTORY_SEPARATOR.$this->fallbackLocale;

        return $this->groups($localePath)
            ->mapWithKeys(function (TranslationGroup $group) use ($langPath, $locale) {
                return [$group->name() => $this->getGroupSourceTranslations($group, $langPath, $locale)];
            })->when($locale !== $this->fallbackLocale, function ($collection) use ($langPath) {
                return $this->mergeMissingSourceKeys($collection, $langPath);
            })->all();
    }

    /**
     * Get all of the registered loader namespaces.
     */
    protected function getNamespaces(): Collection
    {
        return collect(app('translation.loader')->namespaces());
    }

    /**
     * Get the translation groups for the given path.
     */
    public function groups(string $path): Collection
    {
        return collect([])
            ->when(File::isDirectory($path), function (Collection $collection) use ($path) {
                return $collection->concat(File::files($path));
            })
            ->filter(fn (SplFileInfo $file) => $file->getExtension() == 'php')
            ->mapInto(TranslationGroup::class);
    }

    /**
     * Generate JSON language file
     */
    public static function generateJsonLanguageFile(): void
    {
        (new JsonGenerator())->generateTo(config('translator.json'));
    }

    /**
     * Create new locale
     */
    public function createLocale(string $locale, bool $namespaces = false): bool
    {
        $sourceLocale = $this->fallbackLocale;

        // App
        $sourcePath = lang_path($sourceLocale);
        $destination = lang_path($locale);
        $result = File::copyDirectory($sourcePath, $destination);

        // Namespaces
        if ($namespaces) {
            foreach ($this->getNamespaces() as $namespace => $path) {
                if (is_dir($source = $path.DIRECTORY_SEPARATOR.$sourceLocale)) {
                    File::copyDirectory($source, $path.DIRECTORY_SEPARATOR.$locale);
                }
            }
        }

        static::generateJsonLanguageFile();

        return $result;
    }

    protected function getGroupSourceTranslations(TranslationGroup $group, string $langPath, string $forLocale)
    {
        $sourcePath = $langPath.DIRECTORY_SEPARATOR.$forLocale.DIRECTORY_SEPARATOR.$group->filename();

        // Group exists in locale
        if (File::isFile($sourcePath)) {
            return require $sourcePath;
        }

        // include from fallback locale
        return $group->sourceTranslations();
    }

    /**
     * Merge any missing source keys.
     */
    protected function mergeMissingSourceKeys(Collection $collection, string $langPath)
    {
        // We will merge any missing keys that are added in the fallback locale
        // but they do not exists in the locale we are retrieving the original translations
        // e.q. user create new locale
        // in new version we add new key in 'fields.test' path, this key won't exists in the user locale
        // we need to merge it becuase json generator will be unable to generate translations
        return $collection->mapWithKeys(function ($translations, $group) use ($langPath) {
            $fallbackPath = $langPath.DIRECTORY_SEPARATOR.$this->fallbackLocale.DIRECTORY_SEPARATOR.$group.'.php';

            if (file_exists($fallbackPath)) {
                $translations = array_replace_recursive(include $fallbackPath, $translations);
            }

            return [$group => $translations];
        });
    }
}
