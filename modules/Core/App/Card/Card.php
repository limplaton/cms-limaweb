<?php
 

namespace Modules\Core\App\Card;

use DateInterval;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use Modules\Core\App\Support\Authorizeable;
use Modules\Core\App\Support\Carbon;
use Modules\Core\App\Support\HasHelpText;
use Modules\Core\App\Support\HasMeta;
use Modules\Core\App\Support\Makeable;

// @ todo, add Authorizeable tests and general test

abstract class Card implements JsonSerializable
{
    use Authorizeable,
        HasHelpText,
        HasMeta,
        Makeable;

    /**
     * The default selected range
     *
     * @var mixed
     */
    public string|int|null $defaultRange = null;

    /**
     * The ranges available for the chart
     */
    public array $ranges = [];

    /**
     * Unit constants
     */
    const BY_MONTHS = 'month';

    const BY_WEEKS = 'week';

    const BY_DAYS = 'day';

    /**
     * The card name/title that will be displayed.
     */
    public ?string $name = null;

    /**
     * Explanation about the card data.
     */
    public ?string $description = null;

    /**
     * The width of the card (full|half).
     */
    public string $width = 'half';

    /**
     * Indicates that the card should be shown only dashboard.
     */
    public bool $onlyOnDashboard = false;

    /**
     * Indicates that the card should be shown only on index.
     */
    public bool $onlyOnIndex = false;

    /**
     * Indicates that the card should refreshed when action is executed.
     */
    public bool $refreshOnActionExecuted = false;

    /**
     * Indicates whether user can be selected.
     *
     * @var bool|int|callable
     */
    public mixed $withUserSelection = false;

    /**
     * Define the card component used on front end.
     */
    abstract public function component(): string;

    /**
     * Get the card value.
     */
    abstract public function value(Request $request): mixed;

    /**
     * Resolve the card value.
     */
    public function resolve(Request $request): mixed
    {
        $resolver = function () use ($request) {
            return $this->value($request);
        };

        if ($request->boolean('reload_cache')) {
            Cache::forget($this->getCacheKey($request));
        }

        if ($cacheFor = $this->cacheFor()) {
            $cacheFor = is_numeric($cacheFor) ? new DateInterval(sprintf('PT%dM', $cacheFor)) : $cacheFor;

            return Cache::remember(
                $this->getCacheKey($request),
                $cacheFor,
                $resolver
            );
        }

        return $resolver();
    }

    /**
     * The card human readable name.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Get the card explanation.
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * Set that the card should be shown only dashboard.
     */
    public function onlyOnDashboard(): static
    {
        $this->onlyOnDashboard = true;

        return $this;
    }

    /**
     * Set that the card should be shown only on index.
     */
    public function onlyOnIndex(): static
    {
        $this->onlyOnIndex = true;

        return $this;
    }

    /**
     * Get the URI key for the card.
     */
    public function uriKey(): string
    {
        return Str::kebab(class_basename(get_called_class()));
    }

    /**
     * Set the card width class.
     */
    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set that the card value should be refreshed when an action is executed.
     */
    public function refreshOnActionExecuted(bool $value = true): static
    {
        $this->refreshOnActionExecuted = $value;

        return $this;
    }

    /**
     * Set if the card has user selection dropdown.
     */
    public function withUserSelection(bool|int|callable $value = true): static
    {
        $this->withUserSelection = $value;

        return $this;
    }

    /**
     * Get the list of the users.
     *
     * @return array|\Illuminate\Support\Collection
     */
    public function users()
    {
        //
    }

    /**
     * Get the card default user id.
     */
    public function getDefaultUserId(): ?int
    {
        $id = $this->getWithUserSelectionValue();

        return is_int($id) ? $id : null;
    }

    /**
     * Get the value from the "withUserSelection" property.
     *
     * @return mixed
     */
    protected function getWithUserSelectionValue()
    {
        return is_callable($this->withUserSelection) ?
            call_user_func($this->withUserSelection, $this) :
            $this->withUserSelection;
    }

    /**
     * Get the card selected user id.
     */
    protected function getUserId(Request $request): ?int
    {
        if (! $this->authorizedToFilterByUser()) {
            return null;
        }

        // Via user action, allows the "All" users dropdown item to work correctly
        // if by default the card shows only data for the logged-in user.
        if ($request->has('range')) {
            return $request->filled('user_id') ? $request->integer('user_id') : null;
        } else {
            return $this->getDefaultUserId();
        }
    }

    /**
     * Check whether the current user can perform user filter.
     */
    public function authorizedToFilterByUser(): bool
    {
        return true;
    }

    /**
     * Determine for how many minutes the card value should be cached.
     */
    public function cacheFor(): DateTimeInterface|DateInterval|float|int|null
    {
        return null;
    }

    /**
     * Get the cache key for the card.
     */
    public function getCacheKey(Request $request): string
    {
        return sprintf(
            'card.%s.%s.%s',
            $this->uriKey(),
            $this->getCurrentRange($request) ?: 'no-range',
            $this->getUserId($request) ?: 'no-user',
        );
    }

    /**
     * Get the element ranges
     */
    public function ranges(): array
    {
        return $this->ranges;
    }

    /**
     * Get the current range for the given request
     */
    protected function getCurrentRange(Request $request): string|int|null
    {
        return $request->range ?? $this->defaultRange ?? array_keys($this->ranges())[0] ?? null;
    }

    /**
     * Get the available formated ranges
     */
    protected function getFormattedRanges(): array
    {
        return collect($this->ranges() ?? [])->map(function ($range, $key) {
            return ['label' => $range, 'value' => $key];
        })->values()->all();
    }

    /**
     * Determine the proper aggregate starting date.
     *
     * @param  string  $unit
     * @return \Modules\Core\App\Support\Carbon
     */
    protected function getStartingDate($range, $unit)
    {
        $now = Carbon::asCurrentTimezone();
        $range = $range - 1;

        return match ($unit) {
            'month' => $now->subMonths($range)->firstOfMonth()->setTime(0, 0)->inAppTimezone(),
            'week' => $now->subWeeks($range)->startOfWeek()->setTime(0, 0)->inAppTimezone(),
            'day' => $now->subDays($range)->setTime(0, 0)->inAppTimezone(),
            'default' => throw new InvalidArgumentException('Invalid chart unit provided.')
        };
    }

    /*
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'uriKey' => $this->uriKey(),
            'component' => $this->component(),
            'name' => $this->name(),
            'description' => $this->description(),
            'width' => $this->width,
            'withUserSelection' => $this->getWithUserSelectionValue(),
            'users' => $this->users(),
            'refreshOnActionExecuted' => $this->refreshOnActionExecuted,
            'helpText' => $this->helpText,
            'value' => $this->resolve(RequestFacade::instance()),
            'range' => $this->getCurrentRange(RequestFacade::instance()),
            'ranges' => $this->getFormattedRanges(),
        ], $this->meta());
    }
}
