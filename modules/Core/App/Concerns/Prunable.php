<?php
 

namespace Modules\Core\App\Concerns;

use Illuminate\Database\Eloquent\Prunable as LaravelPrunable;

/** @mixin \Modules\Core\App\Models\Model */
trait Prunable
{
    use LaravelPrunable;

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays(
            config('core.soft_deletes.prune_after')
        ));
    }
}
