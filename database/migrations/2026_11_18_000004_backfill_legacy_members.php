<?php

use App\Models\Member;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Member::whereNull('directory_consent_at')->update(['directory_visible' => false]);

        Member::with('user')->chunkById(100, function ($members) {
            foreach ($members as $member) {
                $user = $member->user;
                if (!$user || $user->profile()->exists()) {
                    continue;
                }

                $user->profile()->create([
                    'membership_type' => $member->membership_type,
                    'mobile' => $member->phone,
                    'occupation' => $member->occupation,
                    'business_name' => $member->company_name,
                    'date_of_birth' => $member->date_of_birth,
                    'connection_with_srilanka' => $member->sri_lanka_connection
                        ? [$member->sri_lanka_connection] : [],
                ]);

                $status = match ($member->status) {
                    'active' => 'active',
                    'rejected', 'expired' => 'suspended',
                    default => 'pending',
                };
                if ($user->account_status !== $status) {
                    $user->update(['account_status' => $status]);
                }
            }
        });
    }

    public function down(): void
    {
        // Preserve member profiles and consent changes.
    }
};
