<?php
 

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\App\Models\Media;

class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = config('mediable.aggregate_types');

        $type = $this->faker->randomElement(array_keys($types));

        return [
            'disk' => 'local',
            'directory' => implode('/', $this->faker->words($this->faker->randomDigit)),
            'filename' => $this->faker->filePath(),
            'extension' => $this->faker->randomElement($types[$type]['extensions']),
            'mime_type' => $this->faker->randomElement($types[$type]['mime_types']),
            'aggregate_type' => $type,
            'size' => $this->faker->randomNumber(),
        ];
    }
}
