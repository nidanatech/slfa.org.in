<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = getenv('SLFA_ADMIN_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Set SLFA_ADMIN_PASSWORD in the command environment before running AdminUserSeeder.');
        }

        Validator::make(['password' => $password], [
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();

        $admin = User::firstOrNew(['email' => 'kannan@nidanatech.com']);
        $admin->name = $admin->name ?: 'Kannan';
        $admin->password = Hash::make($password);
        $admin->role = 'admin';
        $admin->account_status = 'active';
        $admin->remember_token = Str::random(60);
        $admin->save();

        $this->command?->info('Administrator account is ready. The password was not printed.');
    }
}
