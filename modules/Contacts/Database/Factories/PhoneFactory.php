<?php
 

namespace Modules\Contacts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\App\Enums\PhoneType;
use Modules\Contacts\App\Models\Phone;

class PhoneFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Phone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => Phone::generateRandomNumber(),
            'type' => PhoneType::cases()[array_rand(PhoneType::cases())],
        ];
    }
}
