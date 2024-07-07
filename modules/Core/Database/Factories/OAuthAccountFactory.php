<?php
 

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\App\Models\OAuthAccount;
use Modules\Users\App\Models\User;

class OAuthAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OAuthAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'google',
            'user_id' => User::factory(),
            'oauth_user_id' => Str::uuid()->__toString(),
            'email' => $this->faker->unique()->safeEmail(),
            'access_token' => Str::uuid()->__toString(),
            'expires' => now()->addDay(5)->timestamp,
        ];
    }

    /**
     * Indicate that the account requires authentication
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function requiresAuth()
    {
        return $this->state(function (array $attributes) {
            return [
                'requires_auth' => true,
            ];
        });
    }
}
