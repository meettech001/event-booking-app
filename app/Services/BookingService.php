<?php
namespace App\Services;

use App\Models\Attendee;
use App\Models\Events;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function book(array $data)
    {
        try {
            $event = Events::findOrFail($data['event_id']);
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages([
                'event_id' => ['Event not found.'],
            ]);
        }

        try {
            $attendee = Attendee::findOrFail($data['attendee_id']);
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages([
                'event_id' => ['Attendee not found.'],
            ]);
        }


        // Check for overbooking
        if ($event->bookings()->count() >= $event->capacity) {
            throw ValidationException::withMessages([
                'event_id' => ['Event is fully booked.'],
            ]);
        }

        // Check for duplicate booking
        if ($event->bookings()->where('attendee_id', $attendee->id)->exists()) {
            throw ValidationException::withMessages([
                'attendee_id' => ['Attendee already booked this event.'],
            ]);
        }

        return DB::transaction(function () use ($event, $attendee) {
            return $event->bookings()->create([
                'attendee_id' => $attendee->id,
            ]);
        });
    }
}
