<?php
 

namespace Modules\Documents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\App\Models\Document;

class DocumentSignerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Documents\App\Models\DocumentSigner::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'document_id' => Document::factory(),
            'send_email' => false,
        ];
    }

    /**
     * Indicate that an email will be sent for this signer.
     */
    public function mailable(): Factory
    {
        return $this->state(function () {
            return [
                'send_email' => true,
            ];
        });
    }

    /**
     * Indicate that the signer has signed the document.
     */
    public function signed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'signature' => $attributes['name'],
                'signed_at' => now(),
                'sign_ip' => $this->faker->ipv4(),
            ];
        });
    }
}
