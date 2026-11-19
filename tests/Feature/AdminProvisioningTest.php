<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
