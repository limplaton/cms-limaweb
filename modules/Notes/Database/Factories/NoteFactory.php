<?php
 

namespace Modules\Notes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notes\App\Models\Note;
use Modules\Users\App\Models\User;

class NoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Note::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body' => $this->faker->paragraph(),
            'user_id' => User::factory(),
        ];
    }
}
