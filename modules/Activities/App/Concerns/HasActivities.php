<?php
 

namespace Modules\Activities\App\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Activities\App\Criteria\ViewAuthorizedActivitiesCriteria;

/** @mixin \Modules\Core\App\Models\Model */
trait HasActivities
{
    /**
     * Get all of the associated activities for the record.
     */
    public function activities(): MorphToMany
    {
        return $this->morphToMany(\Modules\Activities\App\Models\Activity::class, 'activityable');
    }

    /**
     * A record has incomplete activities
     */
    public function incompleteActivities(): MorphToMany
    {
        return $this->activities()->incomplete();
    }

    /**
     * Get the incomplete activities for the user
     */
    public function incompleteActivitiesForUser(): MorphToMany
    {
        return $this->incompleteActivities()->criteria(ViewAuthorizedActivitiesCriteria::class);
    }

    /**
     * Get the model next activity
     */
    public function nextActivity(): BelongsTo
    {
        return $this->belongsTo(\Modules\Activities\App\Models\Activity::class, 'next_activity_id');
    }
}
