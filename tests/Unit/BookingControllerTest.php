<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendee;
use App\Models\Events;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use App\Services\BookingService;
use PHPUnit\Framework\Attributes\Test;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_books_event_successfully()
    {
        $user = User::factory()->create();
        $attendee = Attendee::factory()->create();
        $event = Events::factory()->create();

        $this->actingAs($user);

        $mockBookingService = Mockery::mock(BookingService::class);
        $mockBookingService->shouldReceive('book')->once()->andReturn([
            'attendee_id' => $attendee->id,
            'event_id' => $event->id,
        ]);

        $this->app->instance(BookingService::class, $mockBookingService);

        $response = $this->postJson('/api/booking/book-event', [
            'attendee_id' => $attendee->id,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => "You're booked event successfully",
        ]);
    }

    #[Test]
    public function it_returns_validation_error_when_fields_are_missing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/booking/book-event', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendee_id', 'event_id']);
    }

    #[Test]
    public function it_returns_error_when_booking_service_fails()
    {
        $user = User::factory()->create();
        $attendee = Attendee::factory()->create();
        $event = Events::factory()->create();

        $this->actingAs($user);

        $mockBookingService = Mockery::mock(BookingService::class);
        $mockBookingService->shouldReceive('book')
            ->once()
            ->andThrow(new \Exception('Booking failed.'));

        $this->app->instance(BookingService::class, $mockBookingService);

        $response = $this->postJson('/api/booking/book-event', [
            'attendee_id' => $attendee->id,
            'event_id' => $event->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'errors' => 'Booking failed.'
        ]);
    }
}