<?php
 

namespace Modules\Deals\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Deals\App\Enums\DealStatus;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Pipeline;
use Modules\Users\App\Models\User;

class DealFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Deal::class;

    /**
     * Next deal board order
     */
    protected static int $boardOrder = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'pipeline_id' => Pipeline::factory()->withStages(),
            'stage_id' => function (array $attributes) {
                return Pipeline::find($attributes['pipeline_id'])->stages->random()->id;
            },
            'swatch_color' => $this->faker->hexColor(),
            'status' => DealStatus::open,
            'expected_close_date' => $this->faker->dateTimeBetween('+2 week', '+2 month')->format('Y-m-d'),
            'amount' => $this->faker->randomFloat(0, 1000, 10000),
            'board_order' => static::$boardOrder,
            'created_at' => $this->faker->dateTimeBetween('-7 days')->format('Y-m-d H:i:s'),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the deal is with status open
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function open()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => DealStatus::open,
            ];
        });
    }

    /**
     * Indicate that the deal is with status won
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function won()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => DealStatus::won,
            ];
        });
    }

    /**
     * Indicate that the deal is with status lost
     *
     * @param  string  $reason
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function lost($reason = null)
    {
        return $this->state(function (array $attributes) use ($reason) {
            return [
                'status' => DealStatus::lost,
                'lost_reason' => $reason,
            ];
        });
    }

    /**
     * Indicate that the deal color will be random application color or no color.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function randomColor()
    {
        return $this->state(function (array $attributes) {
            return [
                // Push multiple times null for increasing the chances for no color
                'swatch_color' => collect(Innoclapps::favouriteColors())
                    ->push(null, null, null, null, null)
                    ->random(),
            ];
        });
    }

    /**
     * Configure the model factory.
     *
     * @return self
     */
    public function configure()
    {
        return $this->afterCreating(function (Deal $deal) {
            static::$boardOrder = $deal->board_order + 1;
        });
    }
}
