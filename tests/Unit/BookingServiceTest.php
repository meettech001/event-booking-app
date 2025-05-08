<?php

namespace Tests\Unit\Services;

use App\Models\Attendee;
use App\Models\Bookings;
use App\Models\Events;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $bookingService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::factory()->create();
        $this->bookingService = new BookingService();
    }

    #[Test]
    public function it_throws_validation_exception_when_event_not_found()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Event not found.');

        $data = [
            'event_id' => 999, // Non-existent event
            'attendee_id' => 1,
        ];

        $this->bookingService->book($data);
    }

    #[Test]
    public function it_throws_validation_exception_when_attendee_not_found()
    {
        $event = Events::factory()->create(['user_id' => $this->user->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Attendee not found.');

        $data = [
            'event_id' => $event->id,
            'attendee_id' => 999, // Non-existent attendee
        ];

        $this->bookingService->book($data);
    }

    #[Test]
    public function it_throws_validation_exception_when_attendee_book_past_event()
    {
        $event = Events::factory()->create([
            'user_id' => $this->user->id,
            'capacity' => 10,
            'start_time' => Carbon::now()->addDays(-5),
            'end_time' => Carbon::now()->addDays(-3),

        ]);

        $attendee1 = Attendee::factory()->create();

        // Book first attendee
        $event->bookings()->create(['attendee_id' => $attendee1->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot book a past event.');

        $data = [
            'event_id' => $event->id,
            'attendee_id' => $attendee1->id,
        ];

        $this->bookingService->book($data);
    }

    #[Test]
    public function it_throws_validation_exception_when_event_is_fully_booked()
    {
        $event = Events::factory()->create([
            'user_id' => $this->user->id,
            'capacity' => 1,
            'start_time' => Carbon::now()->addDays(3),
            'end_time' => Carbon::now()->addDays(3),
        ]);

        $attendee1 = Attendee::factory()->create();
        $attendee2 = Attendee::factory()->create();

        // Book first attendee
        $event->bookings()->create(['attendee_id' => $attendee1->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Event has no available slots.');

        $data = [
            'event_id' => $event->id,
            'attendee_id' => $attendee2->id,
        ];

        $this->bookingService->book($data);
    }


    #[Test]
    public function it_throws_validation_exception_for_duplicate_booking()
    {
        $event = Events::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => Carbon::now()->addDays(3),
            'end_time' => Carbon::now()->addDays(3)
        ]);
        $attendee = Attendee::factory()->create();

        // Create initial booking
        $event->bookings()->create(['attendee_id' => $attendee->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Attendee already booked this event.');

        $data = [
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
        ];

        $this->bookingService->book($data);
    }

    #[Test]
    public function it_successfully_creates_a_booking()
    {
        $event = Events::factory()->create([
            'user_id' => $this->user->id,
            'capacity' => 2,
            'start_time' => Carbon::now()->addDays(3),
            'end_time' => Carbon::now()->addDays(3),

        ]);
        $attendee = Attendee::factory()->create();

        $data = [
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
        ];

        $booking = $this->bookingService->book($data);

        $this->assertDatabaseHas('bookings', [
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
        ]);

        $this->assertEquals($event->id, $booking->event_id);
        $this->assertEquals($attendee->id, $booking->attendee_id);
    }

    public function test_race_condition_handling()
    {
        $event = Events::factory()->create(['capacity' => 1, 'start_time' => now()->addDay()]);
        $attendees = Attendee::factory()->count(2)->create();

        // Mock the cache lock to simulate contention
        $lock = Cache::lock("event_booking_{$event->id}", 10);
        $lock->get(); // Acquire lock before the test

        try {
            // First attempt should be blocked by our mock lock
            $this->bookingService->book([
                'event_id' => $event->id,
                'attendee_id' => $attendees[0]->id
            ]);
        } finally {
            $lock->release();
        }

        // Now try a successful booking
        $booking = $this->bookingService->book([
            'event_id' => $event->id,
            'attendee_id' => $attendees[0]->id
        ]);

        $this->assertInstanceOf(Bookings::class, $booking);

        // Second attendee should fail due to capacity
        try {
            $this->bookingService->book([
                'event_id' => $event->id,
                'attendee_id' => $attendees[1]->id
            ]);
            $this->fail('Expected capacity validation exception');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('event_id', $e->errors());
            $this->assertEquals('Event has no available slots.', $e->errors()['event_id'][0]);
        }
    }

    #[Test]
    public function it_returns_event_with_bookings_for_valid_user_and_event()
    {
        $user = User::factory()->create();
        $event = Events::factory()->create(['user_id' => $user->id]);

        // Create unique attendees for each booking
        $attendee1 = Attendee::factory()->create();
        $attendee2 = Attendee::factory()->create();
        $attendee3 = Attendee::factory()->create();

        // Create bookings with different attendees
        Bookings::factory()->create([
            'event_id' => $event->id,
            'attendee_id' => $attendee1->id
        ]);
        Bookings::factory()->create([
            'event_id' => $event->id,
            'attendee_id' => $attendee2->id
        ]);
        Bookings::factory()->create([
            'event_id' => $event->id,
            'attendee_id' => $attendee3->id
        ]);

        $result = $this->bookingService->getBookings(['event_id' => $event->id], $user);

        $this->assertEquals($event->id, $result['id']);
        $this->assertArrayHasKey('bookings', $result);
        $this->assertCount(3, $result['bookings']);
        $this->assertEquals(3, $result['bookingcount']);
    }

    #[Test]
    public function it_throws_exception_when_event_not_found()
    {
        $user = User::factory()->create();
        $nonExistentEventId = 999;

        try {
            $this->bookingService->getBookings(['event_id' => $nonExistentEventId], $user);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('event_id', $e->errors());
            $this->assertEquals('Event not found.', $e->errors()['event_id'][0]);
        }
    }
}
