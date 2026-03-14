<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DATABASE CONNECTION ===\n";
echo 'Database: '.config('database.connections.mysql.database')."\n";

echo "\n=== USERS ===\n";
$users = DB::table('users')->select('id', 'name', 'email', 'role', 'is_active')->get();
foreach ($users as $user) {
    echo "{$user->id} | {$user->name} | {$user->email} | {$user->role} | ".($user->is_active ? 'active' : 'inactive')."\n";
}

echo "\n=== CAMPUSES ===\n";
$campuses = DB::table('campuses')->select('id', 'name', 'qr_token')->get();
foreach ($campuses as $campus) {
    echo "{$campus->id} | {$campus->name} | ".substr($campus->qr_token ?? '-', 0, 16)."...\n";
}

echo "\n=== SCHEDULES: ".DB::table('schedules')->count()." ===\n";
