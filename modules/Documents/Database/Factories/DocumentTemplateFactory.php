<?php
 

namespace Modules\Documents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\App\Models\User;

class DocumentTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Documents\App\Models\DocumentTemplate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->text(100),
            'content' => $this->faker->paragraph(),
            'is_shared' => false,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the template is shared.
     */
    public function shared(): Factory
    {
        return $this->state(function () {
            return [
                'is_shared' => true,
            ];
        });
    }
}
