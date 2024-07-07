<?php
 

namespace Modules\Activities\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Activities\App\Concerns\HasActivities;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Import\Import;
use Modules\Core\App\Resource\Resource;

class SyncNextActivityDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:sync-next-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the next activity date field for the related records.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (Import::isImportInProgress()) {
            return;
        }

        foreach (static::resourcesWithNextActivity() as $resource) {
            $this->forResource($resource);
        }
    }

    /**
     * Sync the next activity for the given resource
     */
    protected function forResource(Resource $resource): void
    {
        $resource::model()::unguarded(function () use ($resource) {
            $this->syncFinished($resource->newModel());
            $this->syncNextActivity($resource->newModel());
        });
    }

    /**
     * Get all of the resources with next activity
     */
    public static function resourcesWithNextActivity(): Collection
    {
        return Innoclapps::registeredResources()->filter(
            fn ($resource) => in_array(HasActivities::class, class_uses_recursive($resource::$model))
        );
    }

    /**
     * Sync the resource next activity
     */
    protected function syncNextActivity(Model $model): void
    {
        $attributes = $this->recordsWithIncompleteActivitiesAndInFuture($model)
            ->map(function ($record) {
                $nextActivity = $record->activities->first();

                return array_merge([
                    $record->getKeyName() => $record->getKey(),
                    'next_activity_id' => $nextActivity->getKey(),
                    'next_activity_date' => $nextActivity->next_activity_date,

                ], $record->usesTimestamps() ? [
                    $record->getUpdatedAtColumn() => $record->updated_at->format($record->getDateFormat()),
                ] : []);
            })->all();

        $this->performBatchUpdate($attributes, $model);
    }

    /**
     * Get record that are with incomplete activities and due date is in the future
     */
    protected function recordsWithIncompleteActivitiesAndInFuture(Model $model): Collection
    {
        return $model->newQuery()
            ->select(array_merge(
                [$model->getKeyName()],
                $model->usesTimestamps() ? [$model->getUpdatedAtColumn()] : []
            ))
            ->withWhereHas(
                'activities',
                fn ($query) => $query->incompleteAndInFuture()
                    ->select(['id', Activity::dueDateQueryExpression('next_activity_date')])
                    ->orderBy(Activity::dueDateQueryExpression())
            )
            ->get();
    }

    /**
     * Sync the finished resources
     *
     * Update next activity date to null where the resources doesn't have any incomplete activities
     */
    protected function syncFinished(Model $model): void
    {
        $model::withoutTimestamps(function () use ($model) {
            $model->newQuery()->whereDoesntHave(
                'activities',
                fn ($query) => $query->incomplete()->where(
                    Activity::dueDateQueryExpression(),
                    '>=',
                    now()
                )
            )->update([
                'next_activity_id' => null,
                'next_activity_date' => null,
            ]);
        });
    }

    /**
     * Perform batch next activity date update
     */
    protected function performBatchUpdate(array $attributes, Model $model): void
    {
        $modifiedRecords = batch()->update($model, $attributes, $model->getKeyName());

        if ($modifiedRecords) {
            // Clear the cache as the Batch updater is using direct connection
            // to the database in this case, the model event won't be triggered
            // $this->call('modelCache:clear', ['--model' => $model::class]);
        }
    }
}
