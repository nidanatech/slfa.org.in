<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Calendar;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function registerUser(array $data, string $planSlug)
    {
        return DB::transaction(function () use ($data, $planSlug) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $data['name'] . "'s Business",
            ]);

            // 2. Create User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            // Assign Owner Role (Team ID is handled by User::getPermissionsTeamId via tenant_id)
            $user->assignRole('Owner');

            // Hash password if it looks like it's not hashed yet
            if (Hash::needsRehash($user->password)) {
                $user->password = Hash::make($data['password']);
                $user->save();
            }

            // 3. Create Default Sales Pipeline
            $this->createDefaultPipeline($tenant, $user);

            // 4. Create Default Calendar
            $this->createDefaultCalendar($tenant, $user);

            // 5. Assign Plan
            $this->activateSubscription($tenant, $planSlug, $data['stripe_id'] ?? null);

            return $user;
        });
    }

    public function activateSubscription(Tenant $tenant, string $planSlug, ?string $stripeSubscriptionId = null)
    {
        $plan = Plan::where('slug', $planSlug)->first();
        if (!$plan) return null;

        return Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'type' => 'default',
                'stripe_id' => $stripeSubscriptionId ?? 'free_' . uniqid(),
                'stripe_status' => 'active',
                'stripe_price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]
        );
    }

    protected function createDefaultPipeline(Tenant $tenant, User $user)
    {
        $pipeline = Pipeline::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sales Pipeline',
            'object_type' => 'opportunity',
            'is_default' => true,
            'created_by' => $user->id,
        ]);

        $stages = [
            ['name' => 'Lead', 'order_index' => 1, 'probability' => 10],
            ['name' => 'Qualified', 'order_index' => 2, 'probability' => 25],
            ['name' => 'Proposal', 'order_index' => 3, 'probability' => 50],
            ['name' => 'Negotiation', 'order_index' => 4, 'probability' => 75],
            ['name' => 'Closed Won', 'order_index' => 5, 'probability' => 100],
        ];

        foreach ($stages as $stage) {
            PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                'name' => $stage['name'],
                'order_index' => $stage['order_index'],
                'probability' => $stage['probability'],
            ]);
        }
    }

    protected function createDefaultCalendar(Tenant $tenant, User $user)
    {
        Calendar::create([
            'name' => $user->name . "'s Calendar",
            'calendar_type' => 'user',
            'owner_id' => $user->id,
            'tenant_id' => $tenant->id,
            'timezone' => 'UTC',
            'color' => '#3b82f6',
            'is_default' => true,
            'visibility' => 'private',
            'slug' => strtolower(str_replace(' ', '-', $user->name)) . '-calendar-' . uniqid(),
            'slot_duration' => 30,
            'buffer_time' => 0,
            'is_public' => false,
        ]);
    }
}
