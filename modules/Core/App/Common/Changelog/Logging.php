<?php
 

namespace Modules\Core\App\Common\Changelog;

use Closure;
use Modules\Core\App\Models\Changelog;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Facades\CauserResolver;

class Logging
{
    /**
     * The log trigger method
     */
    protected const LOGER_TRIGGER_METHOD = 'log';

    /**
     * Set the log causer
     *
     * @param  \Modules\Users\App\Models\User|null  $causer
     */
    public function setCauser($causer): void
    {
        CauserResolver::setCauser($causer);
    }

    /**
     * Execute callback in disabled state
     */
    public function disabled(Closure $callback): void
    {
        $this->disable();

        try {
            $callback($this);
        } finally {
            $this->enable();
        }
    }

    /**
     * Disable changes logging
     */
    public function disable(): static
    {
        $this->getLogger()->disableLogging();

        return $this;
    }

    /**
     * Enable changes logging
     */
    public function enable(): static
    {
        $this->getLogger()->enableLogging();

        return $this;
    }

    /**
     * Get the logger
     */
    public function getLogger(): ActivityLogger
    {
        return activity();
    }

    /**
     * Log changes on the given model
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function onModel($model, array $attributes = []): PendingLog
    {
        return $this->on($model)
            ->withProperties($attributes)
            ->generic()
            ->useModelLog();
    }

    /**
     * Get the latest created log for the current request
     *
     * @return null\Modules\Core\App\Models\Changelog
     */
    public function getLatestCreatedLog(): ?Changelog
    {
        return Changelog::$latestSavedLog;
    }

    /**
     * Forward calls to PendingLog or log via the logger
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $logger = new PendingLog($this->getLogger(), self::LOGER_TRIGGER_METHOD);

        // Check direct logging without any arguments
        // e.q. ChangeLogger::log('Log Message');
        if ($name === self::LOGER_TRIGGER_METHOD) {
            return $logger->log($arguments[0] ?? '');
        }

        return $logger->{$name}(...$arguments);
    }
}
