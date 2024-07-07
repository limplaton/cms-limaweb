<?php
 

namespace Modules\Core\App\Common\Changelog;

use GeneaLabs\LaravelPivotEvents\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Models\Model;

trait LogsModelPivotChanges
{
    use PivotEventTrait;

    /**
     * Get the lang attribute for the changelog when logging to the pivot model
     * that the related model is moved to the trash.
     */
    abstract protected static function modelTrashedPivotChangelogLangAttribute($model): array;

    /**
     * Get the lang attribute for the changelog when logging to the pivot model
     * that the related model is restored.
     */
    abstract protected static function modelRestoredPivotChangelogLangAttribute($model): array;

    /**
     * Get the lang attribute for the changelog when logging to the pivot model
     * that the related model is permanently deleted.
     */
    abstract protected static function modelPermanentlyDeletedPivotChangelogLangAttribute($model): array;

    /**
     * Boot the events callbacks.
     */
    protected static function bootLogsModelPivotChanges(): void
    {
        static::deleting(function (Model&Presentable $model) {
            if (! $model->isForceDeleting()) {
                static::logModelTrashedToRelatedPivots($model);
            }
        });

        if (method_exists(static::class, 'restoring')) {
            static::restoring(function (Model&Presentable $model) {
                static::logModelRestoredToRelatedPivots($model);
            });
        }

        static::pivotSynced(function (Model&Presentable $model, $relationName, $changes) {
            if (count($changes['attached']) > 0) {
                static::logPivotAttached($model, $relationName, $changes['attached']);
            }

            if (count($changes['detached']) > 0) {
                static::logPivotDetached($model, $relationName, $changes['detached']);
            }
        });

        static::pivotAttached(function (Model&Presentable $model, $relationName, $pivotIds, $pivotIdsAttributes) {
            static::logPivotAttached($model, $relationName, $pivotIds);
        });

        static::pivotDetached(function (Model&Presentable $model, $relationName, $pivotIds) {
            static::logPivotDetached($model, $relationName, $pivotIds);
        });
    }

    /**
     * Log to the model pivot relations that the model is trashed.
     */
    protected static function logModelTrashedToRelatedPivots(Model&Presentable $model): void
    {
        if (! static::hasDefinedPivotRelationsForChangelog()) {
            return;
        }

        $pivotRelations = array_keys(static::$logPivotEventsOn);

        foreach ($pivotRelations as $relationName) {
            foreach ($model->{$relationName}()->withTrashedIfUsingSoftDeletes()->get() as $pivotModel) {
                ChangeLogger::onModel($pivotModel, [
                    'icon' => 'Trash',
                    'lang' => static::modelTrashedPivotChangelogLangAttribute($model),
                ])->log();
            }
        }
    }

    /**
     * Log to the model pivot relations that the model is restored.
     */
    protected static function logModelRestoredToRelatedPivots(Model&Presentable $model): void
    {
        if (! static::hasDefinedPivotRelationsForChangelog()) {
            return;
        }

        $pivotRelations = array_keys(static::$logPivotEventsOn);

        foreach ($pivotRelations as $relationName) {
            foreach ($model->{$relationName}()->withTrashedIfUsingSoftDeletes()->get() as $relatedModel) {
                ChangeLogger::onModel($relatedModel, [
                    'icon' => 'Trash',
                    'lang' => static::modelRestoredPivotChangelogLangAttribute($model),
                ])->log();
            }
        }
    }

    /**
     * Determine if the model has defined pivot relations.
     */
    protected static function hasDefinedPivotRelationsForChangelog(): bool
    {
        return property_exists(static::class, 'logPivotEventsOn');
    }

    /**
     * Check whether to log the pivot changelog based on the changed relation.
     */
    protected static function shouldLogPivotChange(string $relationName): bool
    {
        if (! static::hasDefinedPivotRelationsForChangelog()) {
            return false;
        }

        return isset(static::$logPivotEventsOn[$relationName]);
    }

    /**
     * Log pivot changelog for the given model and pivots.
     */
    protected static function logPivotChangelog(Model $model, array|Collection $pivotModels, string $action): void
    {
        // Always add on second to the created_at in each loop as usually the log is performed
        // after a record is created and they won't be displayed properly on the timeline
        // e.q. the associated changelog will be first and after that the created changelog.
        $createdAt = now()->addSecond(1);

        foreach ($pivotModels as $pivotModel) {
            $attributes = [
                'id' => $pivotModel->getKey(),
                'name' => $pivotModel->displayName(),
                'path' => $pivotModel->path(),
            ];

            ChangeLogger::onModel($model, $attributes)
                ->identifier($action)
                ->createdAt($createdAt)
                ->log();
        }
    }

    /**
     * Parse the given pivot id's to models.
     */
    protected static function parsePivotIds(Model $model, string $relationName, array $pivotIds): Collection
    {
        return $model->{$relationName}()
            ->getModel()
            ->query()
            ->withTrashedIfUsingSoftDeletes()
            ->whereIn($model->getKeyName(), $pivotIds)
            ->get();
    }

    /**
     * Log change to the model that the given pivot id's are attached.
     */
    protected static function logPivotAttached(Model&Presentable $model, string $relationName, array $pivotIds): void
    {
        if (! static::shouldLogPivotChange($relationName)) {
            return;
        }

        $pivotModels = static::parsePivotIds($model, $relationName, $pivotIds);

        // Log to the main model.
        static::logPivotChangelog($model, $pivotModels, 'attached');

        // Log inverse for each of the pivot models.
        $pivotModels->each(fn (Model $pivotModel) => static::logPivotChangelog($pivotModel, [$model], 'attached'));
    }

    /**
     * Log change to the model that the given pivot id's are detached.
     */
    protected static function logPivotDetached(Model&Presentable $model, string $relationName, array $pivotIds): void
    {
        if (! static::shouldLogPivotChange($relationName)) {
            return;
        }

        $pivotModels = static::parsePivotIds($model, $relationName, $pivotIds);

        // Log to the main model.
        if (! method_exists($model, 'isForceDeleting') || ! $model->isForceDeleting()) {
            static::logPivotChangelog($model, $pivotModels, 'detached');
        }

        if ($model->isForceDeleting()) {
            $pivotModels->each(function ($pivotModel) use ($model) {
                ChangeLogger::onModel($pivotModel, [
                    'icon' => 'Trash',
                    'lang' => static::modelPermanentlyDeletedPivotChangelogLangAttribute($model),
                ])->log();
            });

            return;
        }

        // Log inverse for each of the pivot models.
        $pivotModels->each(
            fn (Model $pivotModel) => static::logPivotChangelog($pivotModel, [$model], 'detached')
        );
    }
}
