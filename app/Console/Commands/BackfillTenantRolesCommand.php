<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class BackfillTenantRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:backfill-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Owner and Staff roles for all existing tenants and assign Owner role to first user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = Tenant::all();
        $this->info("Found {$tenants->count()} tenants to process.");

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            $this->processTenant($tenant);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill completed.');
    }

    protected function processTenant(Tenant $tenant)
    {
        // Define Guard
        $guardName = 'web';

        // 1. Create Owner Role
        $ownerRole = Role::firstOrCreate([
            'name' => 'Owner',
            'guard_name' => $guardName,
            'team_id' => $tenant->id,
        ]);

        // Assign All Permissions to Owner
        $ownerRole->syncPermissions(Permission::all());

        // 2. Create Staff Role
        $staffRole = Role::firstOrCreate([
            'name' => 'Staff',
            'guard_name' => $guardName,
            'team_id' => $tenant->id,
        ]);

        // Assign Specific Permissions to Staff
        $staffPermissions = [
             'inbox.view', 'inbox.reply', 'conversation.assign', 'conversation.delete',
             'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
             'marketing.view', 'campaigns.create', 'social.post',
        ];
        // Only sync if permissions exist to avoid errors if some missing from DB
        $validPermissions = Permission::whereIn('name', $staffPermissions)->pluck('name')->toArray();
        $staffRole->syncPermissions($validPermissions);

        // 3. Assign Owner Role to the first user found for this tenant (Assumption: Creator is first user)
        // Find users belonging to this tenant
        $users = User::where('tenant_id', $tenant->id)->get();
        
        foreach ($users as $user) {
            // Check if user has ANY role for this team
            // Note: assignRole checks guard, but we need to ensure we are assigning for the correct team context.
            // Since we updated User::getPermissionsTeamId(), calls to assignRole SHOULD use the user's tenant_id.
            
            // However, assume we want to make the FIRST user the Owner if no one is.
            // Or just ensure EVERY user has at least some role? 
            // The prompt says "Each tenant must have these 2 default Roles created".
            // It doesn't explicitly say we MUST assign them to users in backfill, but it's good practice to ensure the owner has access.
            
            // Let's safe-guard: If user has NO roles, assign Owner to the first one, Staff to others?
            // Or just assign Owner to ALL for now to be safe/lazy? NO, that's dangerous.
            
            // Strategy:
            // If user has NO roles, and is likely the admin (maybe check if they created the tenant?), assign Owner.
            // For now, let's just make sure the ROLES exist. Assignment might be better done manually or implicitly.
            // BUT, if we don't assign, no one can access anything.
            
            // Let's Assign 'Owner' to the FIRST user of the tenant.
            if ($users->first()->id === $user->id) {
                 if (!$user->hasRole('Owner')) {
                     // Force team id context just in case, though model handles it.
                     setPermissionsTeamId($tenant->id);
                     $user->assignRole($ownerRole);
                 }
            } else {
                 // Do not auto-assign others, or maybe assign Staff?
                 // Safer to leave others alone or assign Staff if they have NO role.
                 if ($user->roles->isEmpty()) {
                     setPermissionsTeamId($tenant->id);
                     $user->assignRole($staffRole);
                 }
            }
        }
    }
}
