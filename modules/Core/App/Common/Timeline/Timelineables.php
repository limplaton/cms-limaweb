<?php
 

namespace Modules\Core\App\Common\Timeline;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Finder\Finder;

class Timelineables
{
    /**
     * @var \Illuminate\Support\Collection|null
     */
    protected static $models;

    /**
     * Discover and register the timelineables
     */
    public static function discover(): void
    {
        $instance = new static;

        $timelineables = $instance->getTimelineables()->all();

        foreach ($instance->getSubjects() as $subject) {
            static::register($timelineables, $subject);
        }
    }

    /**
     * Register the given timelineables
     */
    public static function register(string|array $timelineables, string $subject): void
    {
        Timeline::acceptsPinsFrom([
            'subject' => $subject,
            'as' => $subject::getTimelineSubjectKey(),
            'accepts' => array_map(function ($class) {
                return ['as' => $class::timelineKey(), 'timelineable_type' => $class];
            }, Arr::wrap($timelineables)),
        ]);
    }

    /**
     * Get the timelineables
     */
    public function getTimelineables(): Collection
    {
        return $this->getModels()
            ->filter(fn ($model) => static::isTimelineable($model))
            ->values();
    }

    /**
     * Check whether the given model is timelineable
     *
     * @param  \Modules\Core\App\Models\Model|string  $model
     */
    public static function isTimelineable($model): bool
    {
        return in_array(Timelineable::class, class_uses_recursive($model));
    }

    /**
     * Check whether the given model has timeline
     *
     * @param  \Modules\Core\App\Models\Model|string  $model
     */
    public static function hasTimeline($model): bool
    {
        return in_array(HasTimeline::class, class_uses_recursive($model));
    }

    /**
     * Get the subjects
     */
    public function getSubjects(): Collection
    {
        return $this->getModels()
            ->filter(function ($model) {
                return in_array(HasTimeline::class, class_uses_recursive($model));
            })->values();
    }

    /**
     * Get the application models
     */
    protected function getModels(): Collection
    {
        if (static::$models) {
            return static::$models;
        }

        $modulesPaths = collect(Module::allEnabled())
            ->map(fn ($module) => module_path($module->getLowerName(), config('modules.paths.generator.model.path')))
            ->filter(fn ($path) => file_exists($path))
            ->values()
            ->all();

        $paths = array_merge([app_path('Models')], $modulesPaths);
        $finder = (new Finder)->in($paths)->files()->name('*.php');

        return static::$models = collect($finder)
            ->map(function ($model) {
                if (str_contains($model->getRealPath(), config('modules.paths.modules'))) {
                    return config('modules.namespace').'\\'.str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        Str::after($model->getRealPath(), realpath(config('modules.paths.modules')).DIRECTORY_SEPARATOR)
                    );
                }

                return app()->getNamespace().str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
                );
            });
    }
}
