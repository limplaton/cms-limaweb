<?php
 

namespace Modules\Core\App\Settings;

class SettingsMenu
{
    protected static array $items = [];

    /**
     * Register new settings menu item.
     */
    public static function register(SettingsMenuItem $item, string $id): void
    {
        static::$items[$id] = $item->setId($id);
    }

    /**
     * Add children menu item to existing item.
     */
    public static function add(string $id, SettingsMenuItem $item)
    {
        if (! array_key_exists($id, static::$items)) {
            return;
        }

        static::$items[$id]->withChild($item, $item->getId());
    }

    /**
     * Find menu item by the given id.
     */
    public static function find(string $id): ?SettingsMenuItem
    {
        return collect(static::$items)->first(fn (SettingsMenuItem $item) => $item->getId() === $id);
    }

    /**
     * Get all of the registered settings menu items.
     */
    public static function all(): array
    {
        return collect(static::$items)->sortBy('order')->values()->all();
    }
}
