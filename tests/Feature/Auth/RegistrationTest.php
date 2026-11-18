<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Storage::fake('local');
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'membership_type' => 'individual',
            'directory_consent' => false,
            'profile' => [
                'address' => 'Chennai',
                'mobile' => '9000000000',
                'connection_with_srilanka' => ['Ancestry'],
            ],
            'id_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('members', ['email' => 'test@example.com', 'directory_visible' => false]);
        $path = \App\Models\User::where('email', 'test@example.com')->firstOrFail()->profile->id_proof_path;
        Storage::disk('local')->assertExists($path);
    }
}
