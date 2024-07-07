<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource as BaseJsonResource;
use Modules\Core\App\Common\Timeline\Timelineables;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Contracts\Primaryable;
use Modules\Core\App\Support\GateHelper;

/** @mixin \Modules\Core\App\Models\Model */
class JsonResource extends BaseJsonResource
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected static $topLevelResource;

    /**
     * Set the top level resource
     *
     * @param  \Illuminate\Database\Eloquent\Model  $resource
     * @return void
     */
    public static function topLevelResource($resource)
    {
        static::$topLevelResource = $resource;
    }

    /**
     * Provide common data for the resource
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    protected function withCommonData(array $data, $request): array
    {
        array_unshift($data, $this->merge([
            'id' => $this->getKey(),
        ]));

        if ($this->resource instanceof Presentable) {
            $data['display_name'] = $this->displayName();
            $data['path'] = $this->path();
        }

        if ($this->resource instanceof Primaryable) {
            $data['is_primary'] = $this->isPrimary();
        }

        if ($this->usesTimestamps()) {
            $data[$this->getCreatedAtColumn()] = $this->{$this->getCreatedAtColumn()};
            $data[$this->getUpdatedAtColumn()] = $this->{$this->getUpdatedAtColumn()};
        }

        if (! $request->isZapier()) {
            if (Timelineables::isTimelineable($this->resource)) {
                $data['timeline_component'] = $this->getTimelineComponent();
                $data['timeline_relation'] = $this->getTimelineRelation();
                $data['timeline_key'] = $this->timelineKey();
                $data['timeline_sort_column'] = $this->getTimelineSortColumn();

                if (static::$topLevelResource &&
                        $this->relationLoaded('pinnedTimelineSubjects')) {
                    $pinnedSubject = $this->getPinnedSubject(static::$topLevelResource::class, static::$topLevelResource->getKey());

                    $data['is_pinned'] = ! is_null($pinnedSubject);
                    $data['pinned_date'] = $pinnedSubject?->created_at;
                }
            }

            if (Timelineables::hasTimeline($this->resource)) {
                $data['timeline_subject_key'] = $this->getTimelineSubjectKey();
            }

            if ($authorizations = GateHelper::authorizations($this->resource)) {
                $data['authorizations'] = $authorizations;
            }

            $data['was_recently_created'] = $this->wasRecentlyCreated;
        }

        return $data;
    }
}
