<?php
 

namespace Modules\Calls\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Modules\Calls\Database\Factories\CallOutcomeFactory;
use Modules\Core\App\Models\CacheModel;
use Modules\Core\App\Resource\Resourceable;

class CallOutcome extends CacheModel
{
    use HasFactory,
        Resourceable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'swatch_color',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::deleting(function (CallOutcome $model) {
            if ($model->calls()->count() > 0) {
                abort(409, __('calls::call.outcome.delete_warning'));
            }
        });
    }

    /**
     * A call outcome can have many associated calls
     */
    public function calls(): HasMany
    {
        return $this->hasMany(\Modules\Calls\App\Models\Call::class);
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

            $customKey = 'custom.call_outcome.'.$attributes['id'];

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
    protected static function newFactory(): CallOutcomeFactory
    {
        return CallOutcomeFactory::new();
    }
}
