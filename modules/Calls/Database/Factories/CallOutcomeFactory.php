<?php
 

namespace Modules\Calls\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calls\App\Models\CallOutcome;

class CallOutcomeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CallOutcome::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->catchPhrase(),
            'swatch_color' => $this->faker->hexColor(),
        ];
    }
}
