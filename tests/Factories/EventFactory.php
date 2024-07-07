<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\Event;
use Tests\Fixtures\EventStatus;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+3 weeks');
        // Round to nearest 15
        $roundedDueSeconds = round($start->getTimestamp() / (15 * 60)) * (15 * 60);
        $start->setTime($start->format('H'), date('i', $roundedDueSeconds), 0);

        $end = clone $start;
        // Add one or zero days to end date and the add 30 minutes
        $end->add(new \DateInterval('P'.rand(0, 1).'D'));
        $end->add(new \DateInterval('PT30M'));

        return [
            'title' => $this->faker->text(100),
            'total_guests' => rand(0, 4),
            'end' => $start,
            'status_id' => EventStatus::factory(),
            'start' => $end,
            'description' => $this->faker->text(100),
            'is_all_day' => $this->faker->boolean(),
            'user_id' => null,
        ];
    }
}
