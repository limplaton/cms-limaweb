<?php
 

namespace Modules\MailClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MailClient\App\Models\PredefinedMailTemplate;
use Modules\Users\App\Models\User;

class PredefinedMailTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PredefinedMailTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->text(100),
            'subject' => $this->faker->text(100),
            'body' => '<p>'.$this->faker->paragraph().'</p',
            'is_shared' => true,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the template is personal.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function personal()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_shared' => false,
            ];
        });
    }

    /**
     * Indicate that the template is shared.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function shared()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_shared' => true,
            ];
        });
    }
}
