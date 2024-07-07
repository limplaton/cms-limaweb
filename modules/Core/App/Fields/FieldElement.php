<?php
 

namespace Modules\Core\App\Fields;

use Modules\Core\App\Support\Authorizeable;
use Modules\Core\App\Support\HasMeta;

abstract class FieldElement
{
    use Authorizeable,
        HasMeta;

    /**
     * Indicates whether this field is not hidden on index view
     */
    public bool $showOnIndex = true;

    /**
     * @var bool|callable
     */
    public $applicableForIndex = true;

    /**
     * Indicates whether this field is not hidden on create view
     */
    public bool $showOnCreation = true;

    /**
     * @var bool|callable
     */
    public $applicableForCreation = true;

    /**
     * Indicates whether this field is not hidden on update view
     */
    public bool $showOnUpdate = true;

    /**
     * @var bool|callable
     */
    public $applicableForUpdate = true;

    /**
     * Indicates whether this field is not hidden on detail view
     */
    public bool $showOnDetail = true;

    /**
     * @var bool|callable
     */
    public $applicableForDetail = true;

    /**
     * Indicates whether this field is excluded from setttings
     */
    public bool|string|array $excludeFromSettings = false;

    /**
     * Indicates whether to exclude the field from import
     */
    public bool $excludeFromImport = false;

    /**
     * Indicates whether the field should be included in sample data
     */
    public bool $excludeFromImportSample = false;

    /**
     * Indicates whether to exclude the field from export
     */
    public bool $excludeFromExport = false;

    /**
     * Set that the field by default should be hidden on index view
     */
    public function hideFromIndex(): static
    {
        $this->showOnIndex = false;

        return $this;
    }

    /**
     * The field is only for index and cannot be used on other views
     */
    public function onlyOnIndex(): static
    {
        $this->excludeFromSettings = true;
        $this->applicableForIndex = true;
        $this->applicableForCreation = false;
        $this->applicableForUpdate = false;
        $this->applicableForDetail = false;

        return $this;
    }

    /**
     * Set that the field by default should be hidden on create view
     */
    public function hideWhenCreating(): static
    {
        $this->showOnCreation = false;

        return $this;
    }

    /**
     * The field is only for creation and cannot be used on other views
     */
    public function onlyOnCreate(): static
    {
        $this->applicableForCreation = true;
        $this->applicableForUpdate = false;
        $this->applicableForDetail = false;
        $this->applicableForIndex = false;

        return $this;
    }

    /**
     * Set that the field by default should be hidden on update view
     */
    public function hideWhenUpdating(): static
    {
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * The field is only for update and cannot be used on other views
     */
    public function onlyOnUpdate(): static
    {
        $this->applicableForUpdate = true;
        $this->applicableForCreation = false;
        $this->applicableForIndex = false;
        $this->applicableForDetail = false;

        return $this;
    }

    /**
     * Set that the field by default should be hidden on detail view
     */
    public function hideFromDetail(): static
    {
        $this->showOnDetail = false;

        return $this;
    }

    /**
     * The field is only for detail and cannot be used on other views
     */
    public function onlyOnDetail(): static
    {
        $this->applicableForDetail = true;
        $this->applicableForUpdate = false;
        $this->applicableForCreation = false;
        $this->applicableForIndex = false;

        return $this;
    }

    /**
     * The field is only for import and cannot be used on other views
     */
    public function onlyForImport(): static
    {
        $this->applicableForUpdate = false;
        $this->applicableForDetail = false;
        $this->applicableForCreation = false;
        $this->applicableForIndex = false;
        $this->excludeFromExport = true;
        $this->excludeFromSettings = true;
        $this->excludeFromImport = false;

        return $this;
    }

    /**
     * The field is only for forms and cannot be used on other views
     */
    public function onlyOnForms(): static
    {
        $this->applicableForUpdate = true;
        $this->applicableForDetail = true;
        $this->applicableForCreation = true;
        $this->applicableForIndex = false;

        return $this;
    }

    /**
     * The field is only usable on views different then forms
     */
    public function exceptOnForms(bool|callable $exclude = true): static
    {
        $applicable = is_callable($exclude) ? function () use ($exclude) {
            return call_user_func($exclude) !== true;
        } : $exclude !== true;

        $this->applicableForUpdate = $applicable;
        $this->applicableForCreation = $applicable;
        $this->applicableForIndex = true;
        $this->excludeFromImport = true;

        return $this;
    }

    /**
     * Set that the field is excluded from index view
     */
    public function excludeFromIndex(bool|callable $exclude = true): static
    {
        $this->applicableForIndex = is_callable($exclude) ? function () use ($exclude) {
            return call_user_func($exclude) !== true;
        } : $exclude !== true;

        return $this;
    }

    /**
     * Set if the field is excluded from create view
     */
    public function excludeFromCreate(bool|callable $exclude = true): static
    {
        $this->applicableForCreation = is_callable($exclude) ? function () use ($exclude) {
            return call_user_func($exclude) !== true;
        } : $exclude !== true;

        return $this;
    }

    /**
     * Set if the field is excluded from update view
     */
    public function excludeFromUpdate(bool|callable $exclude = true): static
    {
        $this->applicableForUpdate = is_callable($exclude) ? function () use ($exclude) {
            return call_user_func($exclude) !== true;
        } : $exclude !== true;

        return $this;
    }

    /**
     * Set if the field is excluded from detail view
     */
    public function excludeFromDetail(bool|callable $exclude = false): static
    {
        $this->applicableForDetail = is_callable($exclude) ? function () use ($exclude) {
            return call_user_func($exclude) !== true;
        } : $exclude !== true;

        return $this;
    }

    /**
     * Indicates that this field should be excluded from the settings
     */
    public function excludeFromSettings(string|array|bool $view = true): static
    {
        $this->excludeFromSettings = $view;

        return $this;
    }

    /**
     * Set that the field should be excluded from export
     */
    public function excludeFromExport(): static
    {
        $this->excludeFromExport = true;

        return $this;
    }

    /**
     * Set that the field should be excluded from import
     */
    public function excludeFromImport(): static
    {
        $this->excludeFromImport = true;
        $this->excludeFromImportSample = true;

        return $this;
    }

    /**
     * Set that the field should should not be included in sample data
     */
    public function excludeFromImportSample(): static
    {
        $this->excludeFromImportSample = true;

        return $this;
    }

    /**
     * Indicates that the field by default should be hidden on all views.
     */
    public function hidden(): static
    {
        $this->hideWhenUpdating();
        $this->hideWhenCreating();
        $this->hideFromIndex();
        $this->hideFromDetail();

        return $this;
    }

    /**
     * Determine if the field is applicable for creation view
     */
    public function isApplicableForCreation(): bool
    {
        $callback = $this->applicableForCreation;

        return $callback === true || (is_callable($callback) && call_user_func($callback));
    }

    /**
     * Determine if the field is applicable for update view
     */
    public function isApplicableForUpdate(): bool
    {
        $callback = $this->applicableForUpdate;

        return $callback === true || (is_callable($callback) && call_user_func($callback));
    }

    /**
     * Determine if the field is applicable for detail view
     */
    public function isApplicableForDetail(): bool
    {
        $callback = $this->applicableForDetail;

        return $callback === true || (is_callable($callback) && call_user_func($callback));
    }

    /**
     * Determine if the field is applicable for index view
     */
    public function isApplicableForIndex(): bool
    {
        $callback = $this->applicableForIndex;

        return $callback === true || (is_callable($callback) && call_user_func($callback));
    }

    /**
     * Determine if the field excluded from settings.
     */
    public function isExcludedFromSettings(string|array|null $view = null): bool
    {
        if (is_bool($this->excludeFromSettings)) {
            return $this->excludeFromSettings;
        }

        return in_array($view, (array) $this->excludeFromSettings);
    }
}
