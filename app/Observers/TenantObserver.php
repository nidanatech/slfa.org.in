<?php

namespace App\Observers;

use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created(Tenant $tenant): void
    {
        // Define Guard
        $guardName = 'web';

        // 1. Create Owner Role for this Tenant
        $ownerRole = Role::firstOrCreate([
            'name' => 'Owner',
            'guard_name' => $guardName,
            'team_id' => $tenant->id,
        ]);

        // Assign All Permissions to Owner
        $ownerRole->syncPermissions(Permission::all());

        // 2. Create Staff Role for this Tenant
        $staffRole = Role::firstOrCreate([
            'name' => 'Staff',
            'guard_name' => $guardName,
            'team_id' => $tenant->id,
        ]);

        // Assign Specific Permissions to Staff
        $staffPermissions = [
            // Inbox
            'inbox.view',
            'inbox.reply',
            'conversation.assign',
            'conversation.delete',
            
            // Contacts
            'contacts.view',
            'contacts.create',
            'contacts.edit',
            'contacts.delete',
            
            // Marketing
            'marketing.view',
            'campaigns.create',
            'social.post',
        ];

        $staffRole->syncPermissions($staffPermissions);
    }
}
