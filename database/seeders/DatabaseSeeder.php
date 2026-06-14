<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = \App\Models\Tenant::updateOrCreate(
            ['name' => 'Acme Corp'],
            [
                'settings' => [
                    'modules' => [
                        'marketing' => ['enabled' => true],
                        'accounting' => ['enabled' => true],
                    ],
                ]
            ]
        );

        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'account_status' => 'active',
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            PermissionsSeeder::class,
            SystemAdminSeeder::class,
            ChannelTypeSeeder::class,
            AiBotResourceSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
