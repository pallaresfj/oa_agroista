<?php

$host = '127.0.0.1';
$dbname = 'laravel_teaching_assistance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);

    // Check existing users
    $existing = $pdo->query('SELECT email FROM users')->fetchAll(PDO::FETCH_COLUMN);
    echo 'Existing users: '.implode(', ', $existing)."\n\n";

    // Users to create
    $users = [
        ['Admin Soporte', 'soporte@teachingassistance.com', 'soporte', '3001234567', '1234567890'],
        ['Juan Directivo', 'directivo@teachingassistance.com', 'directivo', '3009876543', '0987654321'],
        ['Docente 1', 'docente1@teachingassistance.com', 'docente', '3001110001', '1112223331'],
        ['Docente 2', 'docente2@teachingassistance.com', 'docente', '3001110002', '1112223332'],
        ['Docente 3', 'docente3@teachingassistance.com', 'docente', '3001110003', '1112223333'],
        ['Docente 4', 'docente4@teachingassistance.com', 'docente', '3001110004', '1112223334'],
        ['Docente 5', 'docente5@teachingassistance.com', 'docente', '3001110005', '1112223335'],
    ];

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, phone, identification_number, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())');

    foreach ($users as $u) {
        if (! in_array($u[1], $existing)) {
            $stmt->execute([$u[0], $u[1], $hash, $u[2], $u[3], $u[4]]);
            echo "✓ Created {$u[0]} ({$u[1]})\n";
        } else {
            // Update existing user
            $pdo->prepare('UPDATE users SET password = ?, role = ?, is_active = 1 WHERE email = ?')
                ->execute([$hash, $u[2], $u[1]]);
            echo "↻ Updated {$u[0]} ({$u[1]})\n";
        }
    }

    // Final summary
    echo "\n=== FINAL USERS ===\n";
    $stmt = $pdo->query('SELECT id, name, email, role, is_active FROM users ORDER BY id');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $active = $row['is_active'] ? '✓' : '✗';
        echo "{$row['id']} | {$row['name']} | {$row['email']} | {$row['role']} | $active\n";
    }

    // Verify password
    echo "\n=== PASSWORD VERIFY ===\n";
    $stmt = $pdo->query("SELECT email, password FROM users WHERE email = 'soporte@teachingassistance.com'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $ok = password_verify('password', $user['password']) ? '✓ OK' : '✗ FAILED';
    echo "soporte@teachingassistance.com: $ok\n";

} catch (PDOException $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}
