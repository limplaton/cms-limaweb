<?php
 

namespace Modules\Deals\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Models\Stage;

class PipelineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pipeline::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->catchPhrase(),
        ];
    }

    /**
     * Indicate that the pipeline is primary.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function primary()
    {
        return $this->state(function (array $attributes) {
            return [
                'flag' => Pipeline::PRIMARY_FLAG,
            ];
        });
    }

    /**
     * Indicate that the pipeline has stages.
     *
     * @param  bool|array  $stages
     * @param  string|null  $relationship
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withStages($stages = true, $relationship = 'stages')
    {
        $stages = is_array($stages) ? $stages : $this->factoryStages();
        $count = count($stages);

        return $this->has(Stage::factory()->state(new Sequence(
            ...$stages,
        ))->count($count), $relationship);
    }

    /**
     * Get the factory default stages
     *
     * @return array
     */
    protected function factoryStages()
    {
        return [
            [
                'name' => 'Qualified To Buy',
                'win_probability' => 20,
                'display_order' => 1,
            ],
            [
                'name' => 'Contact Made',
                'win_probability' => 40,
                'display_order' => 2,
            ],
            [
                'name' => 'Presentation Scheduled',
                'win_probability' => 60,
                'display_order' => 3,
            ],
            [
                'name' => 'Proposal Made',
                'win_probability' => 80,
                'display_order' => 4,
            ],
            [
                'name' => 'Appointment scheduled',
                'win_probability' => 100,
                'display_order' => 5,
            ],
        ];
    }
}
