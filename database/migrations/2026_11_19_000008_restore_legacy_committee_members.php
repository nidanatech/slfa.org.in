<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groups = [
            'Leadership' => [
                ['Bose Dharmalingam', 'President'],
                ['Issac Navaneethan Solomans', 'Vice President'],
                ['Syed Mohamed Aminuddin', 'Secretary'],
                ['Kumudhini J. Yogaratnam', 'Treasurer'],
            ],
            'Executive' => [
                ['J T Roche', 'Executive Member'],
                ['Naresh Julian', 'Executive Member'],
                ['K.S.Sri Giriprasath', 'Executive Member'],
                ['Dr. A. Ravisankar', 'Executive Member'],
                ['Richard Allen Vincent', 'Executive Member'],
                ['S. Prakash', 'Executive Member'],
            ],
        ];

        foreach ($groups as $type => $members) {
            foreach ($members as $position => [$name, $role]) {
                $existing = DB::table('committee_members')->where('name', $name)->first();
                if ($existing) {
                    DB::table('committee_members')->where('id', $existing->id)->update([
                        'type' => $type,
                        'tier' => $type === 'Leadership' ? 'present' : 'executive',
                        'show_in_website' => true,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('committee_members')->insert([
                        'name' => $name,
                        'role' => $role,
                        'type' => $type,
                        'tier' => $type === 'Leadership' ? 'present' : 'executive',
                        'show_in_website' => true,
                        'display_order' => $position,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Keep committee edits and photos made by administrators after import.
    }
};
