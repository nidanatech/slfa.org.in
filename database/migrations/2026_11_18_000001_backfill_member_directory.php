<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::where('role', 'member')->whereHas('profile')->with('profile')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    if ($user->member()->exists()) {
                        continue;
                    }

                    $profile = $user->profile;
                    Member::create([
                        'user_id' => $user->id,
                        'full_name' => $user->name,
                        'email' => $user->email,
                        'phone' => $profile->mobile,
                        'membership_type' => $profile->membership_type,
                        'sri_lanka_connection' => implode(', ', $profile->connection_with_srilanka ?? []),
                        'occupation' => $profile->occupation,
                        'company_name' => $profile->business_name,
                        'status' => $user->account_status === 'active' ? 'active' :
                            ($user->account_status === 'suspended' ? 'rejected' : 'pending'),
                        'directory_visible' => false,
                        'joined_at' => $user->account_status === 'active' ? $profile->date_of_enrollment : null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Backfilled member records may receive edits and payments after this migration.
    }
};
