<?php
 

namespace Modules\Activities\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Modules\Activities\Database\Factories\ActivityTypeFactory;
use Modules\Core\App\Contracts\Primaryable;
use Modules\Core\App\Models\CacheModel;
use Modules\Core\App\Resource\Resourceable;

class ActivityType extends CacheModel implements Primaryable
{
    use HasFactory,
        Resourceable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'swatch_color', 'icon',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::deleting(function (ActivityType $model) {
            if ($model->isPrimary()) {
                abort(409, __('activities::activity.type.delete_primary_warning'));
            } elseif (static::getDefaultType() == $model->getKey()) {
                abort(409, __('activities::activity.type.delete_is_default'));
            } elseif ($model->calendars()->count() > 0) {
                abort(409, __('activities::activity.type.delete_usage_calendars_warning'));
            } elseif ($model->activities()->withTrashed()->count() > 0) {
                abort(409, __('activities::activity.type.delete_usage_warning'));
            }
        });
    }

    /**
     * Get the calendars that the type is added as create event type
     */
    public function calendars(): HasMany
    {
        return $this->hasMany(\Modules\Activities\App\Models\Calendar::class);
    }

    /**
     * Set the activity type
     */
    public static function setDefault(int $id): void
    {
        settings(['default_activity_type' => $id]);
    }

    /**
     * Get the activity default type
     */
    public static function getDefaultType(): ?int
    {
        return settings('default_activity_type');
    }

    /**
     * A activity type has many activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\Modules\Activities\App\Models\Activity::class);
    }

    /**
     * Check whether the activity type is primary
     */
    public function isPrimary(): bool
    {
        return ! is_null($this->flag);
    }

    /**
     * Find activity by flag.
     */
    public static function findByFlag(string $flag): ActivityType
    {
        return static::where('flag', $flag)->first();
    }

    /**
     * Name attribute accessor
     *
     * Supports translation from language file
     */
    public function name(): Attribute
    {
        return Attribute::get(function (string $value, array $attributes) {
            if (! array_key_exists('id', $attributes)) {
                return $value;
            }

            $customKey = 'custom.activity_type.'.$attributes['id'];

            if (Lang::has($customKey)) {
                return __($customKey);
            } elseif (Lang::has($value)) {
                return __($value);
            }

            return $value;
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ActivityTypeFactory
    {
        return ActivityTypeFactory::new();
    }
}
