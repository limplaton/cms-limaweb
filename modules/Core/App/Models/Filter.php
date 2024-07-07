<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Modules\Core\App\Concerns\HasMeta;
use Modules\Core\App\Contracts\Metable;
use Modules\Core\Database\Factories\FilterFactory;

class Filter extends CacheModel implements Metable
{
    use HasFactory,
        HasMeta;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'identifier', 'rules', 'is_shared', 'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_shared' => 'boolean',
        'is_readonly' => 'boolean',
        'user_id' => 'int',
    ];

    /**
     * Get the filter owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Filter has many default views
     */
    public function defaults(): HasMany
    {
        return $this->hasMany(FilterDefaultView::class);
    }

    /**
     * Indicates whether the filter is system default
     */
    public function isSystemDefault(): Attribute
    {
        return Attribute::get(
            fn () => is_null($this->user_id)
        );
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

            $customKey = 'custom.filter.'.$attributes['id'];

            if (Lang::has($customKey)) {
                return __($customKey);
            } elseif (Lang::has($value)) {
                return __($value);
            }

            return $value;
        });
    }

    /**
     * Set rules attribute mutator
     *
     * We will check if the passed value is array and there are
     * children defined in the array, if not, we will assume the the
     * children is passed as one big array
     */
    public function rules(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_array($value) && ! array_key_exists('children', $value)) {
                    $value = [
                        'condition' => 'and',
                        'children' => $value,
                    ];
                }

                return Json::encode(is_array($value) ? $value : []);
            },
            get: function ($value) {
                return Json::decode($value ?? '');
            }
        );
    }

    /**
     * Mark filter as default for the given user.
     */
    public function markAsDefault(string|array $views, int $userId): static
    {
        foreach ((array) $views as $view) {
            // We will check if there is current default filter for the view and the user
            // If yes, we will remove this default filter to leave space for the new one
            if ($currentDefault = static::hasDefaultFor($this->identifier, $view, $userId)->first()) {
                $currentDefault->unMarkAsDefault($view, $userId);
            }

            $this->defaults()->create(['user_id' => $userId,  'view' => $view]);
        }

        return $this;
    }

    /**
     * Unmark filter as default for the given user.
     */
    public function unMarkAsDefault(string|array $views, int $userId): static
    {
        foreach ((array) $views as $view) {
            $this->defaults()
                ->where('view', $view)
                ->where('user_id', $userId)
                ->delete();
        }

        return $this;
    }

    /**
     * Scope a query to include default filters for the given filterables.
     */
    public function scopeHasDefaultFor(Builder $query, string $identifier, string $view, int $userId): void
    {
        $query->whereHas('defaults', function ($query) use ($view, $userId) {
            return $query->where('view', $view)->where('user_id', $userId);
        })->ofIdentifier($identifier)->visibleFor($userId);
    }

    /**
     * Scope a query to only include shared filters.
     */
    public function scopeShared(Builder $query): void
    {
        $query->where('is_shared', true);
    }

    /**
     * Find filter by flag.
     */
    public static function findByFlag(string $flag): ?Filter
    {
        return static::where('flag', $flag)->first();
    }

    /**
     * Scope a query to only include filters of the given identifier.
     */
    public function scopeOfIdentifier(Builder $query, string $identifier): void
    {
        $query->where('identifier', $identifier);
    }

    /**
     * Scope a query to only include filters visible for the given user.
     */
    public function scopeVisibleFor(Builder $query, int $userId): void
    {
        $query->where(function ($query) use ($userId) {
            return $query->where('filters.user_id', $userId)
                ->orWhere('is_shared', true)
                ->orWhereNull('user_id');
        });
    }

    /**
     * Scope a query to retrieve filters for the given user.
     */
    public function scopeForUser(Builder $query, int $userId, string $identifier): void
    {
        $query
            ->with([
                'defaults' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->visibleFor($userId)
            ->ofIdentifier($identifier)
            ->orderBy('name');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): FilterFactory
    {
        return new FilterFactory;
    }
}
