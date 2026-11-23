<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('postcode', 20)->nullable()->after('city');
            $table->string('referrer_contact')->nullable()->after('referred_by');
            $table->string('photograph_path')->nullable()->after('id_proof_path');
        });

        DB::table('committee_members')->update([
            'show_in_website' => false,
            'updated_at' => now(),
        ]);

        $members = [
            ['Bose Dharmalingam', 'President', 'Management', 'Ideal Beach Resort', 'Managing Director', 'Mr Bose Dharmalingam.webp'],
            ['Syed Mohamed Aminuddin', 'Secretary', 'Management', 'Veerya Ventures', 'Chief Executive', 'Mr Syed Mohamed Aminuddin.webp'],
            ['Sooriya Kumari', 'Vice President', 'Executive', null, null, 'Mrs Sooriya Kumari.webp'],
            ['Jayaranee Gnanasambandam', 'Treasurer', 'Executive', null, null, 'Ms Jayaranee Gnanasambandam.webp'],
            ['K.S. Sri Giriprasath', 'Joint Secretary', 'Executive', null, null, 'Mr KS Sri Giriprasath.webp'],
            ['Sadananda Prakash', 'Joint Secretary', 'Executive', null, null, 'Mr Sadana Prakash.webp'],
            ['Dr. R.K. Venkatasalam', 'Executive Member', 'Executive', null, null, 'Dr RK Venkatasalam.webp'],
            ['Fawzan Fareid', 'Executive Member', 'Executive', null, null, 'Mr Fawzan Fareid.webp'],
            ['Ravisankar Arumugam', 'Executive Member', 'Executive', null, null, 'Mr Raishankar Arumugam.webp'],
            ['Richard Allen Vincent', 'Executive Member', 'Executive', null, null, 'Mr Richard Allen Vincent.webp'],
            ['Dr. Lankesh', 'Executive Member', 'Executive', null, null, 'Dr Lankesh.webp'],
        ];

        foreach ($members as $order => [$name, $role, $type, $company, $designation, $photo]) {
            DB::table('committee_members')->updateOrInsert(
                ['name' => $name],
                [
                    'role' => $role,
                    'type' => $type,
                    'tier' => $type === 'Management' ? 'present' : 'executive',
                    'company' => $company,
                    'designation' => $designation,
                    'photo_path' => '/images/members/'.$photo,
                    'show_in_website' => true,
                    'display_order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['city', 'postcode', 'referrer_contact', 'photograph_path']);
        });
    }
};
