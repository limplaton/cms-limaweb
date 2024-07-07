<?php
 

namespace Modules\Core\App\Menu;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;
use Modules\Core\App\Support\Authorizeable;
use Modules\Core\App\Support\Makeable;

class MenuItem implements Arrayable, JsonSerializable
{
    use Authorizeable, Makeable;

    /**
     * The menu item id
     */
    public string $id;

    /**
     * Singular name e.q. Contact, translates to Create "Contact"
     */
    public ?string $singularName = null;

    /**
     * Does this item should be shown on quick create section
     */
    public bool $inQuickCreate = false;

    /**
     * Route for quick create
     */
    public ?string $quickCreateRoute = null;

    /**
     * Custom quick create name
     */
    public ?string $quickCreateName = null;

    /**
     * Badge for the sidebar item
     */
    public null|string|int|float|Closure $badge = null;

    /**
     * Badge color variant
     */
    public string $badgeVariant = 'warning';

    /**
     * Badge color variant
     */
    public ?string $keyboardShortcutChar = null;

    /**
     * Initialize new Item instance.
     */
    public function __construct(public string $name, public string $route, public string $icon = '', public ?int $position = null)
    {
        $this->id = Str::slug($route);
    }

    /**
     * Set menu item id
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set menu item icon
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set badge for the menu item
     */
    public function badge(string|int|float|Closure $value): static
    {
        $this->badge = $value;

        return $this;
    }

    /**
     * Set badge variant
     */
    public function badgeVariant(string $value): static
    {
        $this->badgeVariant = $value;

        return $this;
    }

    /**
     * Get badge for the menu item
     */
    public function getBadge(): mixed
    {
        if ($this->badge instanceof \Closure) {
            return ($this->badge)();
        }

        return $this->badge;
    }

    /**
     * Set menu item position
     */
    public function position(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Set menu item singular name
     */
    public function singularName(string $singular): static
    {
        $this->singularName = $singular;

        return $this;
    }

    /**
     * Whether this item should be also included in the quick create section
     */
    public function inQuickCreate(bool $bool = true): static
    {
        $this->inQuickCreate = $bool;

        return $this;
    }

    /**
     * Set the keyboard shortcut character
     */
    public function keyboardShortcutChar(string $char): static
    {
        $this->keyboardShortcutChar = $char;

        return $this;
    }

    /**
     * Custom quick create route
     * Default route is e.q. contacts/create
     */
    public function quickCreateRoute(string $route): static
    {
        $this->quickCreateRoute = $route;

        return $this;
    }

    /**
     * Custom quick create name
     */
    public function quickCreateName(string $name): static
    {
        $this->quickCreateName = $name;

        return $this;
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'singularName' => $this->singularName,
            'route' => $this->route,
            'icon' => $this->icon,
            'inQuickCreate' => $this->inQuickCreate,
            'quickCreateRoute' => $this->quickCreateRoute,
            'quickCreateName' => $this->quickCreateName,
            'position' => $this->position,
            'badge' => $this->getBadge(),
            'badgeVariant' => $this->badgeVariant,
            'keyboardShortcutChar' => $this->keyboardShortcutChar,
        ];
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
