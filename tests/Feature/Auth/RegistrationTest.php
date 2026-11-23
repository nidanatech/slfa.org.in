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
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'membership_type' => 'individual',
            'directory_consent' => false,
            'accept_declaration' => true,
            'profile' => [
                'address' => 'Chennai, Tamil Nadu, India',
                'city' => 'Chennai',
                'postcode' => '600115',
                'mobile' => '9000000000',
                'date_of_birth' => now()->subYears(25)->toDateString(),
                'gender' => 'Others',
                'occupation' => 'Designer',
                'connection_with_srilanka' => ['Ancestral'],
            ],
            'photograph' => UploadedFile::fake()->image('portrait.jpg', 400, 500),
            'id_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('members', ['email' => 'test@example.com', 'directory_visible' => false]);
        $path = \App\Models\User::where('email', 'test@example.com')->firstOrFail()->profile->id_proof_path;
        Storage::disk('local')->assertExists($path);
        $photographPath = \App\Models\User::where('email', 'test@example.com')->firstOrFail()->profile->photograph_path;
        Storage::disk('local')->assertExists($photographPath);
        $this->assertNotNull(\App\Models\User::where('email', 'test@example.com')->firstOrFail()->profile->application_declaration_at);
    }

    public function test_business_application_requires_two_complete_representatives(): void
    {
        Storage::fake('local');
        $data = [
            'name' => 'Primary Contact',
            'email' => 'business@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'membership_type' => 'business',
            'directory_consent' => false,
            'accept_declaration' => true,
            'profile' => [
                'address' => '123 Beach Road, Chennai',
                'mobile' => '+91 9000000000',
                'business_name' => 'Example Trading Ltd',
                'nature_of_business' => 'Company',
            ],
            'business_reps' => [
                ['name' => 'First Representative', 'date_of_birth' => '1980-01-01', 'gender' => 'Male', 'designation' => 'Director', 'email' => 'first@example.com', 'contact_number' => '+91 9000000001'],
                ['name' => 'Second Representative', 'date_of_birth' => '1982-01-01', 'gender' => 'Female', 'designation' => 'Director', 'email' => 'second@example.com', 'contact_number' => '+91 9000000002'],
            ],
            'id_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->post('/register', array_replace($data, ['business_reps' => [$data['business_reps'][0]]]))
            ->assertSessionHasErrors('business_reps');
        $this->assertDatabaseMissing('users', ['email' => 'business@example.com']);

        $this->post('/register', $data)->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('business_representatives', ['name' => 'Second Representative']);
        $this->assertDatabaseHas('members', ['email' => 'business@example.com', 'membership_type' => 'business', 'full_name' => 'Example Trading Ltd']);
    }

    public function test_underage_applicant_and_missing_declaration_are_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Young Applicant',
            'email' => 'young@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'membership_type' => 'individual',
            'directory_consent' => false,
            'profile' => [
                'address' => '123 Beach Road, Chennai',
                'city' => 'Chennai',
                'postcode' => '600115',
                'mobile' => '9000000000',
                'date_of_birth' => now()->subYears(16)->toDateString(),
                'gender' => 'Male',
                'occupation' => 'Student',
                'connection_with_srilanka' => ['Origin'],
            ],
            'photograph' => UploadedFile::fake()->image('portrait.jpg', 400, 500),
            'id_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['profile.date_of_birth', 'accept_declaration']);

        $this->assertDatabaseMissing('users', ['email' => 'young@example.com']);
    }

    public function test_individual_form_does_not_accept_fields_outside_the_source_form(): void
    {
        $this->post('/register', [
            'name' => 'Adult Applicant',
            'email' => 'adult@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'membership_type' => 'individual',
            'directory_consent' => false,
            'accept_declaration' => true,
            'profile' => [
                'address' => '123 Beach Road, Chennai',
                'city' => 'Chennai',
                'postcode' => '600115',
                'mobile' => '9000000000',
                'date_of_birth' => now()->subYears(25)->toDateString(),
                'gender' => 'Female',
                'occupation' => 'Consultant',
                'connection_with_srilanka' => ['Origin'],
            ],
            'family_members' => [['name' => 'Child', 'relationship' => '']],
            'id_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            'photograph' => UploadedFile::fake()->image('portrait.jpg', 400, 500),
        ])->assertSessionHasErrors('family_members');

        $this->assertDatabaseMissing('users', ['email' => 'adult@example.com']);
    }
}
