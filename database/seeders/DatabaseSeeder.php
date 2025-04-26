<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Events;
use App\Models\Attendee;
use App\Models\Booking;
use App\Models\Bookings;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create users
        User::factory(5)->create()->each(function ($user) {
            // Create events for each user
            Events::factory(2)->create([
                'user_id' => $user->id,
            ]);
        });

        // Create attendees
        Attendee::factory(20)->create();

        // Create bookings
        $attendees = Attendee::all();
        $events = Events::all();

        foreach ($attendees as $attendee) {
            // Each attendee can book random 1-2 events
            $randomEvents = $events->random(rand(1, 2));

            foreach ($randomEvents as $event) {
                Bookings::create([
                    'attendee_id' => $attendee->id,
                    'event_id'    => $event->id,
                ]);
            }
        }
    }
}
