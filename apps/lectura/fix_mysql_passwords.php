<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Generate a proper bcrypt hash for "password"
$hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);

echo 'Generated hash: '.substr($hash, 0, 30)."...\n\n";

// Update all users' passwords
$updated = DB::table('users')->update(['password' => $hash]);
echo "Updated {$updated} users\n\n";

// Verify
$users = DB::table('users')->select('id', 'email', 'password')->get();
foreach ($users as $user) {
    $verified = password_verify('password', $user->password) ? '✓' : '✗';
    echo "{$verified} ID:{$user->id} {$user->email}\n";
}
