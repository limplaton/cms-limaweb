<?php
 

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\App\Models\ModelVisibilityGroup;
use Modules\Core\App\Models\ModelVisibilityGroupDependent;

class ModelVisibilityGroupDependentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ModelVisibilityGroupDependent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'model_visibility_group_id' => ModelVisibilityGroup::factory(),
        ];
    }
}
