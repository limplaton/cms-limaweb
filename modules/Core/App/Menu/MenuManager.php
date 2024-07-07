<?php
 

namespace Modules\Core\App\Menu;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MenuManager
{
    /**
     * Hold the main menu items.
     *
     * @var \Modules\Core\App\Menu\MenuItem[]
     */
    protected array $items = [];

    /**
     * Hold the registered menu metrics.
     *
     * @var \Modules\Core\App\Menu\Metric[]
     */
    protected array $metrics = [];

    /**
     * Register menu item(s).
     */
    public function register(MenuItem|array $items): static
    {
        foreach (Arr::wrap($items) as $item) {
            $this->item($item);
        }

        return $this;
    }

    /**
     * Register a single menu item.
     */
    public function item(MenuItem $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Register new menu metric.
     *
     * @param  \Modules\Core\App\Menu\Metric|array<\Modules\Core\App\Menu\Metric>  $metric
     */
    public function metric(Metric|array $metric): static
    {
        $this->metrics = array_merge($this->metrics, Arr::wrap($metric));

        return $this;
    }

    /**
     * Get all of the registered menu metrics.
     *
     * @return \Modules\Core\App\Menu\Metric[]
     */
    public function metrics()
    {
        return $this->metrics;
    }

    /**
     * Get all registered menu items.
     */
    public function get(): Collection
    {
        $items = (new Collection($this->items))->map(
            fn (MenuItem $item) => $this->checkQuickCreateProperties($item)
        );

        $ordered = $this->checkPositions($items);

        return $ordered->filter->authorizedToSee()->values();
    }

    /**
     * Clears all the registered menu items and metrics.
     */
    public function clear(): static
    {
        $this->items = [];
        $this->metrics = [];

        return $this;
    }

    /**
     * Check if order is set and sort the items.
     */
    protected function checkPositions(Collection $items): Collection
    {
        /**
         * If there is no position set, add the index + 5
         */
        $items->each(function (MenuItem $item, int $index) {
            if (! $item->position) {
                $item->position($index + 10);
            }
        });

        /**
         * Sort the items with the actual order
         */
        return $this->sort($items);
    }

    /**
     * Check quick create properties and add default props.
     */
    protected function checkQuickCreateProperties(MenuItem $item): MenuItem
    {
        if ($item->inQuickCreate) {
            if (! $item->quickCreateRoute) {
                $item->quickCreateRoute(rtrim($item->route, '/').'/'.'create');
            }

            if (! $item->quickCreateName) {
                $item->quickCreateName($item->singularName ?? $item->name);
            }
        }

        return $item;
    }

    /**
     * Sort the items.
     */
    protected function sort(Collection $items): Collection
    {
        return $items->sort(function ($a, $b) {
            if ($a->position == $b->position) {
                return 0;
            }

            return ($a->position < $b->position) ? -1 : 1;
        })->values();
    }
}
