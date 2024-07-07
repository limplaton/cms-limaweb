<?php
 

namespace Modules\Core\App\Models;

use Modules\Core\App\Common\Timeline\Timelineable;
use Spatie\Activitylog\Models\Activity as SpatieActivityLog;

class Changelog extends SpatieActivityLog
{
    use Timelineable;

    /**
     * Latests saved activity
     *
     * @var null|\Modules\Core\App\Models\Changelog
     */
    public static $latestSavedLog;

    /**
     * Causer names cache, when importing a lot data helps making hundreds of queries.
     */
    protected static array $causerNames = [];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        /**
         * Automatically set the causer name if the logged in
         * user is set and no causer name provided
         */
        static::creating(function (Changelog $model) {
            if (! $model->causer_name) {
                $model->causer_name = static::causerName($model);
            }
        });

        static::created(function (Changelog $model) {
            static::$latestSavedLog = $model;
        });
    }

    /**
     * Get the causer name for the given model
     *
     * @param  \Modules\Core\App\Models\Changelog  $model
     * @return string|null
     */
    protected static function causerName($model)
    {
        if ($model->causer_id) {
            if (isset(static::$causerNames[$model->causer_id])) {
                return static::$causerNames[$model->causer_id];
            }

            return static::$causerNames[$model->causer_id] = $model->causer?->name;
        }

        return auth()->user()?->name;
    }

    /**
     * Get the relation name when the model is used as activity
     */
    public function getTimelineRelation(): string
    {
        return 'changelog';
    }

    /**
     * Get the activity front-end component
     */
    public function getTimelineComponent(): string
    {
        return $this->identifier;
    }
}
