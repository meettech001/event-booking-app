<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Events;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function book(array $data)
    {
        $event      = $this->findEvent($data['event_id']);
        $attendee   = $this->findAttendee($data['attendee_id']);

        $this->ensureEventIsBookable($event);
        $this->ensureAttendeeNotAlreadyBooked($event, $attendee);

        return $this->bookWithLock($event, $attendee);
    }

    private function findEvent($eventId)
    {
        try {
            return Events::findOrFail($eventId);
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages([
                'event_id' => ['Event not found.'],
            ]);
        }
    }

    private function findAttendee($attendeeId)
    {
        try {
            return Attendee::findOrFail($attendeeId);
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages([
                'attendee_id' => ['Attendee not found.'],
            ]);
        }
    }

    private function ensureEventIsBookable($event)
    {
        if ($event->start_time < now()) {
            throw ValidationException::withMessages([
                'event_id' => ['Cannot book a past event.'],
            ]);
        }

        if ($event->bookings()->count() >= $event->capacity) {
            throw ValidationException::withMessages([
                'event_id' => ['Event has no available slots.'],
            ]);
        }
    }

    private function ensureAttendeeNotAlreadyBooked($event, $attendee)
    {
        if ($event->bookings()->where('attendee_id', $attendee->id)->exists()) {
            throw ValidationException::withMessages([
                'attendee_id' => ['Attendee already booked this event.'],
            ]);
        }
    }

    private function bookWithLock($event, $attendee)
    {
        $lock = Cache::lock("event_booking_{$event->id}", 10);

        try {
            if ($lock->get()) {
                return DB::transaction(function () use ($event, $attendee) {
                    return $event->bookings()->create([
                        'attendee_id' => $attendee->id,
                    ]);
                });
            }
        } finally {
            optional($lock)->release();
        }
    }


    public function getBookings($data, $user)
    {
        $event = Events::with('bookings')->where('user_id', $user->id)->where('id', $data['event_id'])->first();

        if (!$event) {
            throw ValidationException::withMessages([
                'event_id' => ['Event not found.'],
            ]);
        }

        $eventData = $event->toArray();
        $eventData['bookingcount'] = count($eventData['bookings']);

        return $eventData;
    }
}
