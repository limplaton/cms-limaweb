<?php
 

namespace Modules\Core\App\Common\Synchronization;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Core\App\Models\Synchronization;

/** @mixin \Modules\Core\App\Models\Model */
trait Synchronizable
{
    /**
     * Get the synchronizable synchronizer class
     *
     * @return \Modules\Core\App\Contracts\Synchronization\Synchronizable
     */
    abstract public function synchronizer();

    /**
     * Boot the Synchronizable trait
     */
    protected static function bootSynchronizable(): void
    {
        // Start a new synchronization once created.
        static::created(function ($synchronizable) {
            $synchronizable->synchronization()->create();
        });

        // Stop and delete associated synchronization.
        static::deleting(function ($synchronizable) {
            $synchronizable->synchronization->delete();
        });
    }

    /**
     * Get the model synchronization model
     */
    public function synchronization(): MorphOne
    {
        return $this->morphOne(Synchronization::class, 'synchronizable');
    }
}
