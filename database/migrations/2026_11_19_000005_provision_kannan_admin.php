<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $email = 'kannan@nidanatech.com';
        $user = User::where('email', $email)->first();

        if ($user?->role === 'admin') {
            return;
        }

        if ($user) {
            // A verified account may keep its existing sign-in credentials.
            // An unverified account must prove control of this email by resetting
            // a new, unknown password before it can access the admin area.
            if (! $user->hasVerifiedEmail()) {
                $user->password = Hash::make(Str::random(64));
            }
            $user->role = 'admin';
            $user->account_status = 'active';
            $user->save();
            return;
        }

        User::create([
            'name' => 'Kannan',
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'role' => 'admin',
            'account_status' => 'active',
        ]);
    }

    public function down(): void
    {
        // Preserve the account and any changes its owner has made.
    }
};
