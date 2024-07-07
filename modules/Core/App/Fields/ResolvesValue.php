<?php
 

namespace Modules\Core\App\Fields;

/** @mixin \Modules\Core\App\Fields\Field */
trait ResolvesValue
{
    /**
     * Resolve field value callback.
     *
     * @var null|callable
     */
    public $resolveCallback;

    /**
     * Display callback.
     *
     * @var null|callable
     */
    public $displayCallback;

    /**
     * Export value callback.
     *
     * @var null|callable
     */
    public $exportCallback;

    /**
     * Import sample callback.
     *
     * @var null|callable
     */
    public $importSampleValueCallback;

    /**
     * Sample value callback.
     *
     * @var null|callable
     */
    public $sampleValueCallback;

    /**
     * JSON resource callback.
     *
     * @var null|callable
     */
    public $jsonResourceCallback;

    /**
     * Resolve the actual field value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function resolve($model)
    {
        if (is_callable($this->resolveCallback)) {
            return call_user_func_array($this->resolveCallback, [$model, $this->attribute]);
        }

        return $model->{$this->attribute};
    }

    /**
     * Resolve the displayable field value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function resolveForDisplay($model)
    {
        if (is_callable($this->displayCallback)) {
            return call_user_func_array($this->displayCallback, [$model, $this->resolve($model), $this->attribute]);
        }

        return $this->resolve($model);
    }

    /**
     * Resolve the field value for export.
     *
     * @param  \Modules\Core\App\Models\Model  $model
     * @return string|null
     */
    public function resolveForExport($model)
    {
        if (is_callable($this->exportCallback)) {
            return call_user_func_array($this->exportCallback, [$model, $this->resolve($model), $this->attribute]);
        }

        return $this->resolveForDisplay($model);
    }

    /**
     * Get a sample value for import.
     */
    public function sampleValueForImport(): mixed
    {
        if (is_callable($this->importSampleValueCallback)) {
            return call_user_func_array($this->importSampleValueCallback, [$this->attribute]);
        }

        return $this->sampleValue();
    }

    /**
     * Get a sample value for the field.
     */
    public function sampleValue(): mixed
    {
        if (is_callable($this->sampleValueCallback)) {
            return call_user_func_array($this->sampleValueCallback, [$this->attribute]);
        }

        return 'Sample Data';
    }

    /**
     * Resolve the field value for JSON Resource.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array|null
     */
    public function resolveForJsonResource($model)
    {
        if (is_callable($this->jsonResourceCallback)) {
            return call_user_func_array($this->jsonResourceCallback, [$model, $this->attribute]);
        }

        return [$this->attribute => $this->resolve($model)];
    }

    /**
     * Add custom value resolver.
     */
    public function resolveUsing(callable $callback): static
    {
        $this->resolveCallback = $callback;

        return $this;
    }

    /**
     * Add custom display resolver.
     */
    public function displayUsing(callable $callback): static
    {
        $this->displayCallback = $callback;

        return $this;
    }

    /**
     * Add custom export value resolver.
     */
    public function exportUsing(callable $callback): static
    {
        $this->exportCallback = $callback;

        return $this;
    }

    /**
     * Add custom import sample value resolver.
     */
    public function provideImportValueSampleUsing(callable $callback): static
    {
        $this->importSampleValueCallback = $callback;

        return $this;
    }

    /**
     * Add custom sample value resolver.
     */
    public function provideSampleValueUsing(callable $callback): static
    {
        $this->sampleValueCallback = $callback;

        return $this;
    }

    /**
     * Add custom JSON resource callback.
     */
    public function resolveForJsonResourceUsing(callable $callback): static
    {
        $this->jsonResourceCallback = $callback;

        return $this;
    }
}
