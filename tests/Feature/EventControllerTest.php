<?php

// tests/Feature/EventControllerTest.php

namespace Tests\Feature;

use App\Models\Events;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}
