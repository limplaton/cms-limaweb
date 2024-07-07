<?php
 

namespace Modules\Contacts\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Modules\Contacts\Database\Factories\IndustryFactory;
use Modules\Core\App\Models\CacheModel;
use Modules\Core\App\Resource\Resourceable;

class Industry extends CacheModel
{
    use HasFactory,
        Resourceable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::deleting(function (Industry $model) {
            if ($model->companies()->count() > 0) {
                abort(409, __(
                    'core::resource.associated_delete_warning',
                    [
                        'resource' => __('contacts::company.industry.industry'),
                    ]
                ));
            }
            $model->companies()->onlyTrashed()->update(['industry_id' => null]);
        });
    }

    /**
     * An industry has many companies
     */
    public function companies(): HasMany
    {
        return $this->hasMany(\Modules\Contacts\App\Models\Company::class);
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

            $customKey = 'custom.industry.'.$attributes['id'];

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
    protected static function newFactory(): IndustryFactory
    {
        return IndustryFactory::new();
    }
}
