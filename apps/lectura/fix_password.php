<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);

$dbPath = __DIR__.'/database/database.sqlite';
$pdo = new PDO('sqlite:'.$dbPath);

// Update all passwords
for ($i = 1; $i <= 7; $i++) {
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$hash, $i]);
}

$stmt = $pdo->query('SELECT id, email, password FROM users');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $verified = password_verify('password', $user['password']) ? '✓' : '✗';
    echo $verified.' ID:'.$user['id'].' '.$user['email']."\n";
}
