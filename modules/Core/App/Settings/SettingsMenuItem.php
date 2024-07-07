<?php
 

namespace Modules\Core\App\Settings;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Modules\Core\App\Support\Makeable;

class SettingsMenuItem implements Arrayable, JsonSerializable
{
    use Makeable;

    /**
     * Item children
     */
    protected array $children = [];

    protected ?string $id = null;

    public ?int $order = null;

    /**
     * Create new SettingsMenuItem instance.
     */
    public function __construct(protected string $title, protected ?string $route = null, protected ?string $icon = null)
    {
    }

    /**
     * Set the menu item unique identifier.
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the menu item unique identifier.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set the item icon.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the item order.
     */
    public function order(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Register child menu item.
     */
    public function withChild(self $item, string $id): static
    {
        $this->children[$id] = $item->setId($id);

        return $this;
    }

    /**
     * Set the item child items.
     */
    public function setChildren(array $items): static
    {
        $this->children = $items;

        return $this;
    }

    /**
     * Get the item child items.
     */
    public function getChildren(): array
    {
        return collect($this->children)->sortBy('order')->values()->all();
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'route' => $this->route,
            'icon' => $this->icon,
            'children' => $this->getChildren(),
            'order' => $this->order,
        ];
    }

    /**
     * Prepare the item for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
