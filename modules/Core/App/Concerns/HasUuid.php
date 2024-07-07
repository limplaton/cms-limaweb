<?php
 

namespace Modules\Core\App\Concerns;

use Illuminate\Support\Str;

/** @mixin \Modules\Core\App\Models\Model */
trait HasUuid
{
    /**
     * Boot the model uuid generator trait
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (self $model) {
            if (is_null($model->{$model->uuidColumn()})) {
                $model->forceFill([
                    $model->uuidColumn() => $model->generateUuid(),
                ]);
            }
        });
    }

    /**
     * Generate model uuid.
     */
    public function generateUuid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Get the model uuid column name.
     */
    public function uuidColumn(): string
    {
        return 'uuid';
    }
}
