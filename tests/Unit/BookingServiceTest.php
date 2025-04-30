<?php

namespace Tests\Unit\Services;

use App\Models\Attendee;
use App\Models\Events;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function it_throws_validation_exception_when_event_is_fully_booked()
    {
        $event = Events::factory()->create([
            'user_id' => $this->user->id,
            'capacity' => 1
        ]);
        
        $attendee1 = Attendee::factory()->create();
        $attendee2 = Attendee::factory()->create();

        // Book first attendee
        $event->bookings()->create(['attendee_id' => $attendee1->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Event is fully booked.');

        $data = [
            'event_id' => $event->id,
            'attendee_id' => $attendee2->id,
        ];

        $this->bookingService->book($data);
    }

    #[Test]
    public function it_throws_validation_exception_for_duplicate_booking()
    {
        $event = Events::factory()->create(['user_id' => $this->user->id]);
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
            'capacity' => 2
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
}