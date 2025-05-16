<?php

// tests/Feature/EventControllerTest.php

namespace Tests\Unit;

use App\Models\Events;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_event_list_successfully()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
            'password' => Hash::make('password123')
        ]);

        // Create events for this user
        Events::factory()->count(15)->create([
            'user_id' => $user->id,
        ]);

        // Act as the created user
        $response = $this->actingAs($user)->postJson('/api/events');

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the response contains the correct data and pagination
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'title',
                    'description',
                    'start_time',
                    'end_time',
                    'capacity',
                    'country',
                    // Add any other fields returned by your event model
                ],
            ],
            'pagination' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
        ]);

        // Assert that the data returned contains events
        $this->assertCount(10, $response->json('data')); // Default 10 per page
        $this->assertEquals(15, $response->json('pagination.total'));
    }

    #[Test]
    public function it_returns_no_events_message_if_no_events_found()
    {
        // Create a user with no events
        $user = User::factory()->create();

        // Act as the created user
        $response = $this->actingAs($user)->postJson('/api/events');

        // Assert the response status is 200 and the message indicates no events found
        $response->assertStatus(200);
        $response->assertJson([
            'status' => false,
            'message' => 'No events found.',
            'data' => [],
        ]);
    }

    #[Test]
    public function it_requires_authentication_to_list_events()
    {
        // Attempt to access the events without authentication
        $response = $this->postJson('/api/events');

        // Assert the response status is 401 (Unauthorized)
        $response->assertStatus(401);
    }

    #[Test]
    public function test_event_is_created_successfully()
    {
        $user = User::factory()->create();

        $payload = [
            'title'       => 'Laravel Conference',
            'description' => 'A great Laravel event.',
            'start_time'  => now()->addDay()->toDateTimeString(),
            'end_time'    => now()->addDays(2)->toDateTimeString(),
            'capacity'    => 100,
            'country'     => 'in',
        ];

        $response = $this->actingAs($user)->postJson('/api/event/create', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Event created successfully.',
                'data' => [
                    'title' => 'Laravel Conference',
                    'country' => 'in',
                    'capacity' => 100,
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Laravel Conference',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function test_event_creation_fails_due_to_validation()
    {
        $user = User::factory()->create();

        $payload = [
            // Missing title, start_time, etc.
            'capacity' => 50,
            'country' => 'invalid-country',
        ];

        $response = $this->actingAs($user)->postJson('/api/event/create', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    #[Test]
    public function test_event_is_updated_successfully()
    {
        $user = User::factory()->create();
        $event = Events::factory()->create([
            'user_id' => $user->id,
            'country' => 'in',
            'capacity' => 100,
        ]);

        $payload = [
            'user_id'     => $user->id,
            'title'       => 'Updated Event Title',
            'description' => 'Updated description.',
            'start_time'  => now()->addDays(2)->toDateTimeString(),
            'end_time'    => now()->addDays(3)->toDateTimeString(),
            'capacity'    => 150,
            'country'     => 'uk',
        ];

        $response = $this->actingAs($user)->postJson("/api/event/update/{$event->id}", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Event updated successfully.',
                'data' => [
                    'title' => 'Updated Event Title',
                    'country' => 'uk',
                    'capacity' => 150,
                ]
            ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
            'country' => 'uk',
        ]);
    }
    #[Test]
    public function test_event_update_fails_validation()
    {
        $user = User::factory()->create();
        $event = Events::factory()->create([
            'user_id' => $user->id,
        ]);

        $payload = [ // Missing title, start_time, etc.
            'capacity' => 'not-a-number',
            'country' => 'invalid-country',
        ];

        $response = $this->actingAs($user)->postJson("/api/event/update/{$event->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }
    #[Test]
    public function test_event_update_unauthorized_or_not_found()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $event = Events::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $payload = [
            'title'       => 'Hacked Event',
            'description' => 'Trying to hack',
            'start_time'  => now()->addDay()->toDateTimeString(),
            'end_time'    => now()->addDays(2)->toDateTimeString(),
            'capacity'    => 1,
            'country'     => 'usa',
        ];

        $response = $this->actingAs($user)->postJson("/api/event/update/{$event->id}", $payload);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Event not found or unauthorized'
            ]);
    }

    #[Test]
    public function test_event_is_deleted_successfully()
    {
        $user = User::factory()->create();
        $event = Events::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/event/remove/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Event deleted successfully',
            ]);

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    #[Test]
    public function test_event_deletion_fails_if_not_owner()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $event = Events::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/event/remove/{$event->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Event not found or unauthorized',
            ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }

    #[Test]
    public function test_it_fetches_bookings_successfully()
    {
        $user = User::factory()->create();
        $event = Events::factory()->create(['user_id' => $user->id]);

        // Mock the BookingService
        $mockService = Mockery::mock(BookingService::class);
        $mockService->shouldReceive('getBookings')
            ->once()
            ->with(['event_id' => $event->id], $user)
            ->andReturn((object)[ // Mimic the structure of your actual event with bookings
                'id' => $event->id,
                'user_id' => $user->id,
                'title' => 'Laravqqel Meetup1',
                'description' => 'A cool event on Laravel',
                'country' => 'in',
                'start_time' => '2025-04-30 14:00:00',
                'end_time' => '2025-04-30 17:00:00',
                'capacity' => 120,
                'bookings' => [
                    [
                        'id' => 39,
                        'event_id' => $event->id,
                        'attendee_id' => 3,
                        'created_at' => '2025-05-16 16:36:31'
                    ],
                    [
                        'id' => 40,
                        'event_id' => $event->id,
                        'attendee_id' => 1,
                        'created_at' => '2025-05-16 16:36:37'
                    ],
                    [
                        'id' => 41,
                        'event_id' => $event->id,
                        'attendee_id' => 2,
                        'created_at' => '2025-05-16 16:36:42'
                    ]
                ],
                'bookingcount' => 3
            ]);

        $this->app->instance(BookingService::class, $mockService);

        $response = $this->actingAs($user)->postJson('/api/event/show-bookings', [
            'event_id' => $event->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Bookings fetched successfully',
                'data' => [
                    'id' => $event->id,
                    'bookings' => [
                        ['id' => 39, 'event_id' => $event->id, 'attendee_id' => 3],
                        ['id' => 40, 'event_id' => $event->id, 'attendee_id' => 1],
                        ['id' => 41, 'event_id' => $event->id, 'attendee_id' => 2],
                    ],
                    'bookingcount' => 3,
                ],
            ]);
    }


    #[Test]
    public function test_show_bookings_fails_validation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/event/show-bookings', [
            // Missing event_id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_id']);
    }

    #[Test]
    public function test_show_bookings_handles_service_exception()
    {
        $user = User::factory()->create();
        $event = Events::factory()->create(['user_id' => $user->id]);

        // Mock the BookingService to throw an exception
        $mockService = Mockery::mock(BookingService::class);
        $mockService->shouldReceive('getBookings')
            ->andThrow(new \Exception("Something went wrong"));

        $this->app->instance(BookingService::class, $mockService);

        $response = $this->actingAs($user)->postJson('/api/event/show-bookings', [
            'event_id' => $event->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'errors' => 'Something went wrong',
            ]);
    }
}
