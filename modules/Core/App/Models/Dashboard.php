<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Card\Card;
use Modules\Core\App\Facades\Cards;
use Modules\Core\Database\Factories\DashboardFactory;

class Dashboard extends CacheModel
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cards' => 'array',
        'is_default' => 'boolean',
        'user_id' => 'int',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::created(static::handleMarkedAsDefault(...));
        static::updated(static::handleMarkedAsDefault(...));
    }

    /**
     * Handle dashboard marked as default.
     */
    protected static function handleMarkedAsDefault(Dashboard $model): void
    {
        if (($model->wasChanged('is_default') || $model->wasRecentlyCreated) && $model->is_default === true) {
            static::query()->where('id', '!=', $model->id)->update(['is_default' => false]);
        }
    }

    /**
     * Scope a query dashboards for the given user.
     */
    public function scopeByUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Get the user the dashboard belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Get the default available dashboard cards
     *
     * @param  \Modules\Users\App\Models\User|null  $user
     * @return \Illuminate\Support\Collection
     */
    public static function defaultCards($user = null)
    {
        return Cards::registered()->filter->authorizedToSee($user)
            ->reject(fn ($card) => $card->onlyOnIndex === true)
            ->values()
            ->map(function (Card $card, int $index) {
                return ['key' => $card->uriKey(), 'order' => $index + 1];
            });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DashboardFactory
    {
        return DashboardFactory::new();
    }
}
