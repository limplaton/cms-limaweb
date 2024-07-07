<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;
use Modules\Core\App\Concerns\HasDisplayOrder;
use Modules\Core\App\Fields\CustomFieldFileCache;

class CustomFieldOption extends Model
{
    use HasDisplayOrder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_order',
        'swatch_color',
    ];

    /**
     * Indicates if the model has timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_field_id' => 'int',
        'display_order' => 'int',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted()
    {
        static::saved(function () {
            CustomFieldFileCache::refresh();
        });

        static::deleted(function () {
            CustomFieldFileCache::refresh();
        });
    }

    /**
     * A custom field option belongs to custom field
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
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

            $customKey = 'custom.custom_field.options.'.$attributes['id'];

            if (Lang::has($customKey)) {
                return __($customKey);
            } elseif (Lang::has($value)) {
                return __($value);
            }

            return $value;
        });
    }
}
