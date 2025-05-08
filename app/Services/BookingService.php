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

        //Booking fails if the event date has passed
        if($event->start_time < date("Y-m-d H:i:s")){
            throw ValidationException::withMessages([
                'event_id' => ['Cannot book a past event.'],
            ]);
        }

        // Check for overbooking
        if ($event->bookings()->count() >= $event->capacity) {
            throw ValidationException::withMessages([
                'event_id' => ['Event has no available slots.'],
            ]);
        }

        // Check for duplicate booking
        if ($event->bookings()->where('attendee_id', $attendee->id)->exists()) {
            throw ValidationException::withMessages([
                'attendee_id' => ['Attendee already booked this event.'],
            ]);
        }


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


    public function getBookings($data, $user){
        $event = Events::with('bookings')->where('user_id', $user->id)->where('id', $data['event_id'])->first();

        if(!$event){
            throw ValidationException::withMessages([
                'event_id' => ['Event not found.'],
            ]);
        }

        $eventData = $event->toArray();
        $eventData['bookingcount'] = count($eventData['bookings']);

        return $eventData;
    }
}