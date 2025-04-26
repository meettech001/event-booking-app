<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_a_user_successfully()
    {
        // Simulate data for registration
        $data = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Perform the post request to register the user
        $response = $this->postJson('/api/register', $data);

        // Assert that the response status is 201 (created)
        $response->assertStatus(201);

        // Assert that the message, user data, and token are in the response
        $response->assertJson([
            'message' => 'User registered successfully',
            'user' => [
                'name' => 'John Doe',
                'email' => 'johndoe@example.com',
            ]
        ]);

        // Ensure that the user is saved in the database
        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
        ]);
    }

    #[Test]
    public function it_returns_validation_error_if_data_is_invalid()
    {
        // Simulate invalid data for registration (missing required fields)
        $data = [
            'email' => 'invalidemail', // Invalid email format
            'password' => 'short',     // Password too short
            'password_confirmation' => 'different', // Password mismatch
        ];

        // Perform the post request
        $response = $this->postJson('/api/register', $data);

        // Assert that the response status is 422 (unprocessable entity)
        $response->assertStatus(422);

        // Assert that the validation errors are returned
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function it_logs_in_a_user_successfully()
    {
        // Create a user for testing login
        $user = User::factory()->create([
            'password' => Hash::make('password123'), // Make sure the password is hashed
        ]);

        // Simulate login data
        $data = [
            'email' => $user->email,
            'password' => 'password123', // Correct password
        ];

        // Perform the post request to login
        $response = $this->postJson('/api/login', $data);

        // Assert that the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert that the response contains a token
        $response->assertJsonStructure([
            'token',
        ]);
    }

    #[Test]
    public function it_returns_invalid_credentials_error_on_failed_login()
    {
        // Create a user for testing login
        $user = User::factory()->create([
            'password' => Hash::make('password123'), // Hashed password
        ]);

        // Simulate login data with wrong password
        $data = [
            'email' => $user->email,
            'password' => 'wrongpassword', // Incorrect password
        ];

        // Perform the post request to login
        $response = $this->postJson('/api/login', $data);

        // Assert that the response status is 401 (Unauthorized)
        $response->assertStatus(401);

        // Assert that the response contains the invalid credentials message
        $response->assertJson([
            'message' => 'Invalid credentials',
        ]);
    }

    #[Test]
    public function it_returns_validation_error_if_login_data_is_invalid()
    {
        // Simulate invalid data for login (missing email)
        $data = [
            'password' => 'password123',
        ];

        // Perform the post request
        $response = $this->postJson('/api/login', $data);

        // Assert that the response status is 422 (unprocessable entity)
        $response->assertStatus(422);

        // Assert that the validation errors are returned
        $response->assertJsonValidationErrors(['email']);
    }
}
