<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomFieldObject;

class CustomFieldObjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $objects = ['contact', 'company', 'opportunity'];

        foreach ($objects as $name) {
            CustomFieldObject::firstOrCreate(['name' => $name]);
        }
    }
}
