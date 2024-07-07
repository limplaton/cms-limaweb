<?php
 

namespace Modules\Core\App\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Models\Model;

/** @mixin \Modules\Core\App\Models\Model */
trait HasCreator
{
    /**
     * Boot HasCreator trait.
     */
    protected static function bootHasCreator(): void
    {
        static::creating(function (Model $model) {
            $foreignKey = $model->getCreatorForeignKeyName();

            if (is_null($model->{$foreignKey}) && Auth::check()) {
                $model->forceFill([
                    $foreignKey => Auth::id(),
                ]);
            }
        });
    }

    /**
     * A model has creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            \Modules\Users\App\Models\User::class,
            $this->getCreatorForeignKeyName()
        );
    }

    /**
     * Get the creator foreign key name.
     */
    public function getCreatorForeignKeyName(): string
    {
        return 'created_by';
    }
}
