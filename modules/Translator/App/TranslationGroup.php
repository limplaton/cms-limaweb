<?php
 

namespace Modules\Translator\App;

use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class TranslationGroup
{
    public function __construct(protected SplFileInfo $file)
    {
    }

    public function translations(string $locale, ?string $namespace = null)
    {
        $key = $namespace ? ($namespace.'::'.$this->name()) : $this->name();

        // Use fallback to merge any non existent keys
        $fallback = trans($key, [], trans()->getFallback());

        // We will be using Laravel trans helper because if the group does not exists
        // Laravel automatically fallback to the fallback locale, in this case, en
        return array_replace_recursive($fallback, trans($key, [], $locale));
    }

    public function name(): string
    {
        return $this->file->getFilenameWithoutExtension();
    }

    public function sourceTranslations()
    {
        return require $this->fullPath();
    }

    public function filename(): string
    {
        return $this->file->getFilename();
    }

    public function fullPath(): string
    {
        return $this->file->getRealPath();
    }

    public function getPath(): string
    {
        return $this->file->getPath();
    }

    public function locale(): string
    {
        return Str::afterLast($this->getPath(), DIRECTORY_SEPARATOR);
    }
}
