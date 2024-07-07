<?php
 

namespace Modules\Users\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\App\Settings\DefaultSettings;
use Modules\Users\App\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'locale' => 'en',
            'access_api' => true,
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
            'date_format' => DefaultSettings::get('date_format'),
            'time_format' => DefaultSettings::get('time_format'),
            'timezone' => collect(tz()->all())->random(),
        ];
    }
}
