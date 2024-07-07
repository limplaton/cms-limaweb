<?php
 

namespace Modules\Billable\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Billable\Database\Factories\ProductFactory;
use Modules\Core\App\Concerns\HasCreator;
use Modules\Core\App\Concerns\LazyTouchesViaPivot;
use Modules\Core\App\Concerns\Prunable;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Models\CacheModel;
use Modules\Core\App\Resource\Resourceable;

class Product extends CacheModel implements Presentable
{
    use HasCreator,
        HasFactory,
        LazyTouchesViaPivot,
        Prunable,
        Resourceable,
        SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'unit_price' => 'decimal:3',
        'direct_cost' => 'decimal:3',
        'tax_rate' => 'decimal:3',
        'created_by' => 'int',
    ];

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get the product billable products.
     */
    public function billables(): HasMany
    {
        return $this->hasMany(\Modules\Billable\App\Models\BillableProduct::class, 'product_id');
    }

    /**
     * Get the model display name.
     */
    public function displayName(): string
    {
        return $this->name;
    }

    /**
     * Get the URL path.
     */
    public function path(): string
    {
        return "/products/{$this->id}";
    }

    /**
     * Clone the product.
     */
    public function clone(int $userId): Product
    {
        $newModel = $this->replicate(['sku']);
        $newModel->created_by = $userId;
        $newModel->name = clone_prefix($newModel->name);

        $newModel->save();

        return $newModel;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
