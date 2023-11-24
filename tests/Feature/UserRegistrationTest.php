<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration process.
     *
     * @return void
     */
    public function testUserRegistration()
    {
        // Simulate a user visiting the registration page
        $response = $this->get('/register');

        $response->assertStatus(200);

        // Simulate a user submitting the registration form
        $user = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123', // You might want to use bcrypt() for a real password
            'password_confirmation' => 'password123' // Add password_confirmation field
        ];

        // Simulate a POST request to the registration endpoint

        $response = $this->post('/users', $user);
       
         // dd($response->status());
        // Assert that the user was created and redirected to the home page
        $response->assertStatus(302); // 302 is the status code for a redirect
        
        // $response->assertRedirect('/home');

        // Add this line for debugging
      
        $response->assertRedirect('/',[
            'message' => 'User created and logged in'
        ]);

        

        // Optionally, assert that the user exists in the database
        $this->assertDatabaseHas('users', [
            'email' => $user['email'],
        ]);

        // // Optionally, assert that the user is logged in
        $this->assertAuthenticated();
    }
}
