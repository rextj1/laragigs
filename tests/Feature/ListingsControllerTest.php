<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StoreListingTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_can_store_a_new_listing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $listingData = [
            'title' => $this->faker->sentence(),
            'company' => $this->faker->company(),
            'location' => $this->faker->city(),
            'website' => $this->faker->url(),
            'email' => $this->faker->companyEmail(),
            'tags' => 'laravel, api, backend',
            'description' => $this->faker->paragraph(5),
        ];

        $this->post('/listings', $listingData)
            ->assertStatus(302)
            ->assertRedirect('/')
            ->assertSessionHas('message', 'Listing created successfully!');

        $this->assertDatabaseHas('listings', $listingData);
    }

    public function test_edit_returns_edit_view_with_listing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $listing = Listing::factory()->create();

        $response = $this->get('/listings/' . $listing->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewIs('listings.edit');
        $response->assertViewHas('listing');
        $response->assertViewHas('listing', $listing);
    }



    public function test_owner_can_update_listing_data_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $listing = Listing::factory()->create(['user_id' => $user->id]);

        $data = [
            'title' => $this->faker->sentence(),
            'company' => $this->faker->company(),
            'location' => $this->faker->city(),
            'website' => $this->faker->url(),
            'email' => $this->faker->email,
            'tags' => implode(',', $this->faker->words(3)),
            'description' => $this->faker->paragraph,
        ];

        // Act
        $response = $this->put("/listings/{$listing->id}", $data);

        // Assert
        $response->assertStatus(302)
            ->assertRedirect('/'); // Adjust the redirect URL as needed

        $response->assertSessionHas('message', 'Listing updated successfully!');

        // Refresh the listing from the database
        $listing->refresh();

        // Assert that the listing was updated with the new data
        $this->assertEquals($data['title'], $listing->title);
        $this->assertEquals($data['company'], $listing->company);
        $this->assertEquals($data['location'], $listing->location);
        $this->assertEquals($data['website'], $listing->website);
        $this->assertEquals($data['email'], $listing->email);
        $this->assertEquals($data['tags'], $listing->tags);
        $this->assertEquals($data['description'], $listing->description);

        // Optional: Assert that the logo was not changed if not provided in the request
        $this->assertNull($listing->logo);

        // Act: Simulate uploading a new logo
        $logo = UploadedFile::fake()->image('new_logo.jpg');
        $data['logo'] = $logo;

        $response = $this->put("/listings/{$listing->id}", $data);

        // Assert: Verify the logo and its storage
        $response->assertStatus(302)
            ->assertRedirect('/'); // Adjust the redirect URL as needed

        $response->assertSessionHas('message', 'Listing updated successfully!');

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'logo' => 'logos/' . $logo->hashName(),
        ]);
    }

    public function test_owner_can_delete_listing_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $listing = Listing::factory()->create(['user_id' => $user->id]);

        // Act: Simulate deleting the listing
        $response = $this->delete("/listings/{$listing->id}");

        // Assert: Verify the response and database state
        $response->assertStatus(302)
            ->assertRedirect('/'); // Adjust the redirect URL as needed

        $response->assertSessionHas('message', 'Listing deleted successfully');

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);

        // Optional: Assert that the associated logo file is deleted from storage
        if ($listing->logo) {
            $this->assertFileDoesNotExist(storage_path('app/public/' . $listing->logo));
        }
    
    }

    public function test_authenticated_user_can_access_manage_view()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create listings associated with the authenticated user
        $listings = Listing::factory(3)->create(['user_id' => $user->id]);

        // Act: Simulate accessing the manage view
        $response = $this->get('/listings/manage');

        // Assert: Verify the response and view content
        $response->assertStatus(200)
            ->assertViewIs('listings.manage'); // Adjust the view name as needed

        // Verify that the view contains the listings associated with the user
        foreach ($listings as $listing) {
            $response->assertSee($listing->title);
        }
    }


}
