<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_members_cannot_access_admin_pages(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/admin')->assertForbidden();
        $this->actingAs($member)->get('/admin/members')->assertForbidden();
    }

    public function test_admin_pages_load_for_administrators(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['admin.dashboard', 'admin.payments.index', 'admin.messages.index', 'admin.albums.index'] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
    }

    public function test_legacy_member_records_gain_profiles_without_assuming_directory_consent(): void
    {
        $user = User::factory()->create(['role' => 'member', 'account_status' => 'pending']);
        Member::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'membership_type' => 'individual',
            'status' => 'active',
            'directory_visible' => true,
        ]);

        $migration = require database_path('migrations/2026_11_18_000004_backfill_legacy_members.php');
        $migration->up();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'account_status' => 'active']);
        $this->assertDatabaseHas('members', ['user_id' => $user->id, 'directory_visible' => false]);
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);
    }

    public function test_approval_updates_the_directory_and_keeps_contact_details_private(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'member', 'account_status' => 'pending']);
        $directoryMember = Member::create([
            'user_id' => $applicant->id,
            'full_name' => $applicant->name,
            'email' => $applicant->email,
            'phone' => '9000000000',
            'membership_type' => 'individual',
            'status' => 'pending',
            'directory_visible' => true,
            'show_contact_info' => false,
        ]);

        $this->actingAs($admin)->patch(route('admin.members.approve', $applicant))->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $applicant->id, 'account_status' => 'active']);
        $this->assertDatabaseHas('members', ['id' => $directoryMember->id, 'status' => 'active']);

        $response = $this->actingAs($applicant->refresh())->get(route('directory.show', $directoryMember));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('SLFA/MemberProfile')
            ->missing('member.email')
            ->missing('member.phone')
            ->missing('member.date_of_birth'));
    }

    public function test_payment_verification_cannot_mark_a_member_paid(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->post(route('payment.verify'), [
            'member_id' => 1,
            'amount' => 1,
            'purpose' => 'admission',
        ])->assertStatus(503);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_member_documents_are_stored_privately_and_require_an_active_account(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'member', 'account_status' => 'pending']);

        $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => 'Membership guide',
            'category' => 'registration',
            'file' => UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $document = Document::firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
        $this->actingAs($applicant)->get(route('documents.download', $document))->assertForbidden();

        $applicant->update(['account_status' => 'active']);
        $this->actingAs($applicant->refresh())->get(route('documents.download', $document))->assertOk();
    }
}
