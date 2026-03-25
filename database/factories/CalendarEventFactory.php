<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+10 days');
        $endsAt = (clone $startsAt)->modify('+1 hour');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'location' => fake()->optional()->city(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_APPOINTMENT,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'color' => null,
            'user_id' => User::factory(),
            'created_by_user_id' => User::factory(),
            'updated_by_user_id' => User::factory(),
        ];
    }
}
