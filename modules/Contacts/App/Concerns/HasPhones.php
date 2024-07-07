<?php
 

namespace Modules\Contacts\App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Contacts\App\Enums\PhoneType;
use Modules\Contacts\App\Models\Phone;
use Modules\Core\App\Models\Model;

/** @mixin \Modules\Core\App\Models\Model */
trait HasPhones
{
    /**
     * Boot the HasPhones trait
     */
    protected static function bootHasPhones(): void
    {
        static::deleting(function (Model $model) {
            if ($model->isReallyDeleting()) {
                $model->phones()->delete();
            }
        });
    }

    /**
     * A model has phone number
     */
    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'phoneable')->orderBy('phones.created_at');
    }

    /**
     * Scope a query to include records by phone.
     */
    public function scopeByPhone(Builder $query, string $phone, ?PhoneType $type = null): void
    {
        $query->whereHas('phones', function ($query) use ($phone, $type) {
            if ($type) {
                $query->where('type', $type);
            }

            return $query->where('number', $phone);
        });
    }
}
