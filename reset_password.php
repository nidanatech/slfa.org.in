<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'kannan.kumar82@gmail.com')->first();

if ($user) {
    $newPassword = 'password123';
    $user->password = bcrypt($newPassword);
    $user->save();
    
    echo "✅ Password reset successfully!\n";
    echo "Email: {$user->email}\n";
    echo "New Password: {$newPassword}\n";
    echo "\nYou can now login with these credentials.\n";
} else {
    echo "❌ User with email 'kannan.kumar82@gmail.com' not found.\n";
    echo "\nLet me show you all users:\n";
    $users = App\Models\User::all(['id', 'name', 'email']);
    foreach ($users as $u) {
        echo "- {$u->name} ({$u->email})\n";
    }
}
