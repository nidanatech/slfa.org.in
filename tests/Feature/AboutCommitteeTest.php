<?php

namespace Tests\Feature;

use App\Models\CommitteeMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AboutCommitteeTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_lists_restored_management_and_executive_members(): void
    {
        $this->assertSame(2, CommitteeMember::where('type', 'Management')->where('show_in_website', true)->count());
        $this->assertSame(9, CommitteeMember::where('type', 'Executive')->where('show_in_website', true)->count());

        $this->get(route('about'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SLFA/About')
            ->has('leadershipSetup', 2)
            ->has('executiveCommittee', 9)
            ->where('leadershipSetup.0.name', 'Bose Dharmalingam')
            ->where('executiveCommittee.0.name', 'Sooriya Kumari'));
    }

    public function test_legacy_management_type_is_also_shown(): void
    {
        CommitteeMember::create([
            'name' => 'Additional Manager', 'role' => 'Manager', 'type' => 'Management',
            'show_in_website' => true, 'display_order' => 20,
        ]);

        $this->get(route('about'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('leadershipSetup', 3)
            ->where('leadershipSetup.2.name', 'Additional Manager'));
    }
}
