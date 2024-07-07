<?php
 

namespace Modules\Core\App\Common\Timeline;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\App\Common\Changelog\LogsModelChanges;
use Modules\Core\App\Common\Changelog\LogsModelPivotChanges;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\PinnedTimelineSubject;

/** @mixin \Modules\Core\App\Models\Model */
trait HasTimeline
{
    use LogsModelChanges,
        LogsModelPivotChanges;

    /**
     * Boot the HasTimeline trait
     */
    protected static function bootHasTimeline(): void
    {
        static::deleting(function (Model $model) {
            if ($model->isReallyDeleting()) {
                $model->loadMissing('pinnedTimelineables')
                    ->pinnedTimelineables
                    ->each(function (PinnedTimelineSubject $pin) {
                        $pin->delete();
                    });
            }
        });
    }

    /**
     * Get the timeline subject key
     */
    public static function getTimelineSubjectKey(): string
    {
        return strtolower(class_basename(get_called_class()));
    }

    /**
     * Get the subject pinned timelineables models
     */
    public function pinnedTimelineables(): MorphMany
    {
        return $this->morphMany(PinnedTimelineSubject::class, 'subject');
    }

    /**
     * Get the lang attribute for the changelog when logging to the pivot model
     * that the related model is moved to the trash.
     */
    protected static function modelTrashedPivotChangelogLangAttribute($model): array
    {
        return [
            'key' => 'core::timeline.associate_trashed',
            'attrs' => ['displayName' => $model->displayName()],
        ];
    }

    /**
     * Get the lang attribute for the changelog when logging to the pivot model
     * that the related model is restored.
     */
    protected static function modelRestoredPivotChangelogLangAttribute($model): array
    {
        return [
            'key' => 'core::timeline.association_restored',
            'attrs' => ['associationDisplayName' => $model->displayName()],
        ];
    }

    /**
     * Get the lang attribute for the changelog when logging to the pivot model
     * that the related model is permanently deleted.
     */
    protected static function modelPermanentlyDeletedPivotChangelogLangAttribute($model): array
    {
        return [
            'key' => 'core::timeline.association_permanently_deleted',
            'attrs' => ['associationDisplayName' => $model->displayName()],
        ];
    }
}
