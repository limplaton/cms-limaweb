<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Database\Factories\ModelVisibilityGroupDependentFactory;

class ModelVisibilityGroupDependent extends Model
{
    use HasFactory;

    /**
     * Indicates if the model has timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * That the model that it's visibilty depends from this dependent.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ModelVisibilityGroup::class, 'model_visibility_group_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ModelVisibilityGroupDependentFactory
    {
        return ModelVisibilityGroupDependentFactory::new();
    }
}
