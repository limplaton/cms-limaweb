<?php
 

namespace Modules\Activities\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\ActivityType;
use Modules\Users\App\Models\User;

class ActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$dueDate, $endDate] = $this->getDates();

        return [
            'title' => $this->faker->catchPhrase(),
            'description' => $this->faker->sentence(),
            'note' => $this->faker->sentence(),
            'due_date' => $dueDate->format('Y-m-d'),
            'due_time' => $dueDate->format('H:i:00'),
            'end_date' => $endDate->format('Y-m-d'),
            'end_time' => $endDate->format('H:i:00'),
            'reminder_minutes_before' => 30,
            'user_id' => User::factory(),
            'owner_assigned_date' => now(),
            'activity_type_id' => ActivityType::factory(),
            'created_at' => $this->faker->dateTimeBetween('-7 days')->format('Y-m-d H:i:s'),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the activity has no reminder.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function noReminder()
    {
        return $this->state(function (array $attributes) {
            return [
                'reminder_minutes_before' => null,
            ];
        });
    }

    /**
     * Indicate that the activity is all day.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function allDay()
    {
        return $this->state(function (array $attributes) {
            return [
                'due_time' => null,
                'end_time' => null,
            ];
        });
    }

    /**
     * Indicate that the activity is completed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'completed_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the activity is incomplete.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inProgress()
    {
        return $this->state(function (array $attributes) {
            return [
                'completed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the activity is reminded.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function reminded()
    {
        return $this->state(function (array $attributes) {
            return [
                'reminded_at' => now(),
            ];
        });
    }

    /**
     * Get the dates for the activity
     *
     * @return array
     */
    protected function getDates()
    {
        $dueDate = $this->faker->dateTimeBetween('now', '+6 weeks');
        // Round to nearest 15
        $roundedDueSeconds = round($dueDate->getTimestamp() / (15 * 60)) * (15 * 60);
        $dueDate->setTime($dueDate->format('H'), date('i', $roundedDueSeconds), 0);

        $endDate = clone $dueDate;
        // Add one or zero days to end date and the add 30 minutes
        $endDate->add(new \DateInterval('P'.rand(0, 1).'D'));
        $endDate->add(new \DateInterval('PT30M'));

        return [$dueDate, $endDate];
    }
}
