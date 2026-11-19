<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_requested_admin_is_created_and_can_access_admin_area(): void
    {
        $admin = User::where('email', 'kannan@nidanatech.com')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->account_status);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_seeder_uses_a_password_supplied_at_run_time(): void
    {
        putenv('SLFA_ADMIN_PASSWORD=StrongTestPassword!42');
        try {
            (new AdminUserSeeder)->run();
        } finally {
            putenv('SLFA_ADMIN_PASSWORD');
        }

        $admin = User::where('email', 'kannan@nidanatech.com')->firstOrFail();
        $this->assertTrue(Hash::check('StrongTestPassword!42', $admin->password));
        $this->assertSame('admin', $admin->role);
    }
}
