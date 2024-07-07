<?php
 

namespace Modules\Core\App\Macros;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\Rule;
use Modules\Core\App\Contracts\Criteria\QueryCriteria as QueryCriteriaContract;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Macros;
use Modules\Core\App\Macros\Carbon\FormatsDates;
use Modules\Core\App\Support\Carbon;
use Nwidart\Modules\Facades\Module;

trait RegistersMacros
{
    /**
     * Register application core macros.
     */
    public function registerMacros(): void
    {
        $this->registerCarbonMacros();
        $this->registerStrMacros();
        $this->registerArrMacros();
        $this->registerSchedulerMacros();
        $this->registerRuleMacros();
        $this->registerRequestMacros();
        $this->registerTestResponseMacros();
        $this->registerFilesystemMacros();
        $this->registerUrlMacros();
        $this->registerModuleMacros();
        $this->registerCollectionMacros();
        $this->registerCurrencyMacros();
        $this->registerEloquentBuilderMacros();
        $this->registerSchemaBuilderMacros();
    }

    protected function registerCarbonMacros(): void
    {
        Carbon::mixin(FormatsDates::class);
        CarbonImmutable::mixin(FormatsDates::class);
    }

    protected function registerStrMacros(): void
    {
        Str::macro('isBase64Encoded', new Macros\Str\IsBase64Encoded);
        Str::macro('clickable', new Macros\Str\ClickableUrls);
    }

    protected function registerArrMacros(): void
    {
        Arr::macro('toObject', new Macros\Arr\ToObject);
    }

    protected function registerRuleMacros(): void
    {
        Rule::macro('requiredIfMethodPost', fn (Request $request) => Rule::requiredIf($request->isMethod('POST')));
        Rule::macro('requiredIfMethodPut', fn (Request $request) => Rule::requiredIf($request->isMethod('PUT')));
    }

    protected function registerRequestMacros(): void
    {
        Request::macro('isZapier', fn () => $this->header('user-agent') === 'Zapier');
        Request::macro('getWith', fn () => Str::of($this->get('with', ''))->explode(';')->filter()->all());
        Request::macro('isForTimeline', fn () => $this->boolean('timeline'));
    }

    protected function registerTestResponseMacros(): void
    {
        TestResponse::macro('assertActionUnauthorized', fn () => $this->assertJson(['error' => __('users::user.not_authorized')]));
        TestResponse::macro('assertActionOk', fn () => $this->assertJsonMissingExact(['error' => __('users::user.not_authorized')]));
    }

    protected function registerFilesystemMacros(): void
    {
        Filesystem::macro('deepCleanDirectory', new Macros\Filesystem\DeepCleanDirectory);
    }

    protected function registerUrlMacros(): void
    {
        URL::macro('asAppUrl', fn (string $extra = '') => rtrim(config('app.url'), '/').($extra ? '/'.$extra : ''));
    }

    protected function registerModuleMacros(): void
    {
        Module::macro('core', function () {
            return array_filter($this->all(), function ($module) {
                return in_array($module->getName(), \DetachedHelper::CORE_MODULES);
            });
        });
    }

    protected function registerCollectionMacros(): void
    {
        Collection::macro('trim', function ($character_mask = " \t\n\r\0\x0B") {
            /** @var \Illuminate\Support\Collection */
            $collection = $this;

            return $collection->map(fn ($value) => trim($value, $character_mask));
        });
    }

    protected function registerCurrencyMacros(): void
    {
        Currency::macro('toMoney', function (string|int|float $value, bool $convert = true) {
            /** @var \Akaunting\Money\Currency */
            $currency = $this;

            return new Money(! is_float($value) ? (float) $value : $value, $currency, $convert);
        });
    }

    protected function registerEloquentBuilderMacros(): void
    {
        EloquentBuilder::macro('criteria', function ($criteria) {
            if ($criteria instanceof QueryCriteriaContract || is_string($criteria)) {
                $criteria = [$criteria];
            }

            if (is_iterable($criteria)) {
                foreach ($criteria as $instance) {
                    if (is_string($instance)) {
                        $instance = new $instance;
                    }

                    $instance->apply($this);
                }
            }

            return $this;
        });
    }

    protected function registerSchemaBuilderMacros(): void
    {
        SchemaBuilder::macro('getIndexesForColumn', function (string $table, string $column) {
            return collect(Schema::getIndexes($table))
                ->filter(fn (array $index) => in_array($column, $index['columns']))
                ->values()
                ->all();
        });

        SchemaBuilder::macro('getForeignKeysForColumn', function (string $table, string $column) {
            return collect(Schema::getForeignKeys($table))
                ->filter(fn (array $index) => in_array($column, $index['columns']))
                ->values()
                ->all();
        });
    }

    protected function registerSchedulerMacros(): void
    {
        Schedule::macro('safeCommand', function ($command, array $parameters = []) {
            /** @var \Illuminate\Console\Scheduling\Schedule */
            $scheduler = $this;

            if (! Innoclapps::canRunProcess()) {
                return $scheduler->call(function () use ($command, $parameters) {
                    Artisan::call($command, $parameters);
                });
            }

            return $scheduler->command($command, $parameters);
        });
    }
}
