<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_stores_a_listing()
    {
        // Assuming you have a user logged in
        $user = User::factory()->create();
        $this->actingAs($user);

        // Generate valid listing data
        $listingData = [
            'title' => $this->faker->sentence,
            'company' => $this->faker->company,
            'location' => $this->faker->city,
            'website' => $this->faker->url,
            'email' => $this->faker->email,
            'tags' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph,
        ];

        // Mock file upload (if needed)
        Storage::fake('public');
        $file = UploadedFile::fake()->image('logo.jpg');
        $listingData['logo'] = $file;

        // Make a POST request to the store method
        $response = $this->post('/listings', $listingData);

        // Assert that the response has a redirect status code
        $response->assertStatus(302);

        // Assert that the listing was created in the database
        $this->assertDatabaseHas('listings', [
            'title' => $listingData['title'],
            'company' => $listingData['company'],
            // Add other fields as needed
        ]);

        // Assert that the logo was stored in the storage
        Storage::disk('public')->assertExists('logos/' . $file->hashName());

        // Assert a flash message is set
        $response->assertSessionHas('message', 'Listing created successfully!');
    }
}
