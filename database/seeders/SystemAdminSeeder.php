<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the first System Admin
        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'System Administrator',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
                'is_system_user' => true,
                'system_role' => 'admin',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        echo "System Admin created/updated: admin@admin.com / admin123\n";
    }
}
