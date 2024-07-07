<?php
 

namespace Modules\Core\App\Resource;

use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Fields\ID;

trait ResolvesFields
{
    /**
     * Get the resource available fields.
     */
    public static function getFields(?string $view = null): FieldsCollection
    {
        return Fields::get(static::name(), $view);
    }

    /**
     * Resolve the resource authorized fields.
     */
    public function resolveFields(?string $view = null): FieldsCollection
    {
        return static::getFields($view)->authorized();
    }

    /**
     * Resolve the fields available for creation.
     */
    public function fieldsForCreate(): FieldsCollection
    {
        return $this->resolveFields(Fields::CREATE_VIEW)->filterForCreation();
    }

    /**
     * Get only the visible creation fields.
     */
    public function visibleFieldsForCreate(): FieldsCollection
    {
        return $this->fieldsForCreate()->visibleOnCreate();
    }

    /**
     * Resolve the fields available for update.
     */
    public function fieldsForUpdate(): FieldsCollection
    {
        return $this->resolveFields(Fields::UPDATE_VIEW)->filterForUpdate();
    }

    /**
     * Get only the visible update fields.
     */
    public function visibleFieldsForUpdate(): FieldsCollection
    {
        return $this->fieldsForUpdate()->visibleOnUpdate();
    }

    /**
     * Resolve the fields available for detail.
     */
    public function fieldsForDetail(): FieldsCollection
    {
        return $this->resolveFields(Fields::DETAIL_VIEW)->filterForDetail();
    }

    /**
     * Get only the visible detail fields.
     */
    public function visibleFieldsForDetail(): FieldsCollection
    {
        return $this->fieldsForDetail()->visibleOnDetail();
    }

    /**
     * Get the fields for index.
     */
    public function fieldsForIndex(): FieldsCollection
    {
        $fields = $this->resolveFields();

        if ($fields->whereInstanceOf(ID::class)->isEmpty()) {
            $fields->push(ID::make($this->newModel()->getKeyName())->hidden());
        }

        return $fields->filterForIndex();
    }

    /**
     * Get the fields intended for import sample.
     */
    public function fieldsForImportSample(): FieldsCollection
    {
        return $this->resolveFields()->filterForImportSample();
    }

    /**
     * Get the fields for import.
     */
    public function fieldsForImport(): FieldsCollection
    {
        return $this->resolveFields()->filterForImport();
    }

    /**
     * Get the fields for export.
     */
    public function fieldsForExport(): FieldsCollection
    {
        return $this->resolveFields()->filterForExport();
    }

    /**
     * Get the fields for placeholders.
     */
    public function fieldsForPlaceholders(): FieldsCollection
    {
        return $this->resolveFields()->filterForPlaceholders();
    }
}
