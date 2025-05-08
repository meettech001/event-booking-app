<?php
use App\Models\Attendee;
use App\Models\Events;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
class AttendeeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_registers_an_attendee_successfully()
    {
        $response = $this->postJson('/api/attendee/register', [
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => "You're registered successfully",
                     'user' => [
                         'name' => 'John Doe',
                         'email' => 'john@example.com',
                     ],
                 ]);

        $this->assertDatabaseHas('attendee', [
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function test_register_attendee_fails_validation_on_missing_fields()
    {
        $response = $this->postJson('/api/attendee/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email']);
    }

    #[Test]
    public function test_register_attendee_fails_on_duplicate_email()
    {
        Attendee::create([
            'name'  => 'Existing User',
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/attendee/register', [
            'name'  => 'New User',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_it_returns_events_with_valid_filters()
    {
        $user = User::factory()->create();

        Events::factory()->count(3)->create([
            'title' => 'Test Title',
            'user_id' => $user->id,
            'country' => 'uk',
            'start_time' => '2025-06-01 10:00:00',
            'end_time' => '2025-06-05 10:00:00',
        ]);

        $payload = [
            'title' => 'Test',
            'country' => 'uk',
            'start_time' => '2025-06-01',
            'end_time' => '2025-06-05',
        ];

        $response = $this->actingAs($user)->postJson('/api/attendee/events', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'message' => 'Event list fetched successfully',
                 ])
                 ->assertJsonStructure([
                     'data',
                     'pagination' => [
                         'total',
                         'per_page',
                         'current_page',
                         'last_page',
                     ],
                 ]);
    }

    #[Test]
    public function test_it_returns_validation_error_on_invalid_filters()
    {
        $user = User::factory()->create();

        $payload = [
            'country' => 'invalid',      // not in:in,uk,usa
            'start_time' => 'not-a-date' // invalid date
        ];

        $response = $this->actingAs($user)->postJson('/api/attendee/events', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['country', 'start_time']);
    }

    #[Test]
    public function test_it_returns_empty_when_no_matching_events()
    {
        $user = User::factory()->create();

        $payload = [
            'country' => 'uk',
            'start_time' => '2030-01-01'
        ];

        $response = $this->actingAs($user)->postJson('/api/attendee/events', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => false,
                     'message' => 'No events found.',
                     'data' => [],
                 ]);
    }
}
