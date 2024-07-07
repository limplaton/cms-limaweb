<?php
 

namespace Modules\Calls\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calls\App\Models\Call;
use Modules\Calls\App\Models\CallOutcome;
use Modules\Users\App\Models\User;

class CallFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Call::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body' => $this->faker->paragraph(),
            'date' => $this->faker->dateTimeBetween('-6 months')->format('Y-m-d H:i').':00',
            'call_outcome_id' => CallOutcome::factory(),
            'user_id' => User::factory(),
        ];
    }
}
