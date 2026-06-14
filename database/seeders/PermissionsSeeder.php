<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions List (Grouped)
        $permissions = [
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
            
            // AI Bot
            'aibot.view',
            'aibot.create',
            'aibot.edit',
            'aibot.update',

            // Knowledge Base
            'knowledge_base.view',
            'knowledge_base.create',
            'knowledge_base.edit',
            'knowledge_base.update',

            // Opportunities
            'opportunities.view',
            'opportunities.create',
            'opportunities.edit',
            'opportunities.update',

            // Settings
            'settings.view',
            'settings.manage_team',
            'settings.billing',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Roles
        $ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);

        // Assign Permissions to Owner (All Permissions)
        $ownerRole->syncPermissions(Permission::all());

        // Assign Permissions to Staff (Restricted)
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
        
        // Ensure tokens match what was created above or fetch by name to be safe, 
        // but passing array of strings works with syncPermissions if they exist.
        $staffRole->syncPermissions($staffPermissions);

        echo "Permissions seeded and Default Roles updated.\n";
    }
}
