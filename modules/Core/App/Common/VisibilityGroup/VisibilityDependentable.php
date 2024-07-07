<?php
 

namespace Modules\Core\App\Common\VisibilityGroup;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\ModelVisibilityGroupDependent;

/** @mixin \Modules\Core\App\Models\Model */
trait VisibilityDependentable
{
    /**
     * Boot the "VisibilityDependentable" trait.
     */
    protected static function bootVisibilityDependentable(): void
    {
        static::deleting(function (Model $model) {
            if ($model->isReallyDeleting()) {
                $model->visibilityDependents()->delete();
            }
        });
    }

    /**
     * Get all of the visibility dependent models.
     */
    public function visibilityDependents(): MorphMany
    {
        return $this->morphMany(ModelVisibilityGroupDependent::class, 'dependable');
    }
}
