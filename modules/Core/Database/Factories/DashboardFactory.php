<?php
 

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\App\Models\Dashboard;
use Modules\Users\App\Models\User;

class DashboardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Dashboard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->catchPhrase(),
            'is_default' => false,
            'user_id' => User::factory(),
            'cards' => [],
        ];
    }

    /**
     * Indicate that the dashboard is default.
     */
    public function default(): Factory
    {
        return $this->state(function () {
            return [
                'is_default' => true,
            ];
        });
    }
}
