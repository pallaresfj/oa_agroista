<?php
// Connect directly to MySQL without Laravel to bypass .env permission issues
$host = '127.0.0.1';
$dbname = 'laravel_teaching_assistance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to: $dbname\n\n";
    
    // Check users
    echo "=== USERS ===\n";
    $stmt = $pdo->query("SELECT id, name, email, role, is_active FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "{$u['id']} | {$u['name']} | {$u['email']} | {$u['role']} | " . ($u['is_active'] ? 'active' : 'inactive') . "\n";
    }
    
    // Generate and update password
    $hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);
    echo "\n=== FIXING PASSWORDS ===\n";
    echo "New hash: " . substr($hash, 0, 30) . "...\n";
    
    $stmt = $pdo->prepare("UPDATE users SET password = ?");
    $stmt->execute([$hash]);
    echo "Updated " . $stmt->rowCount() . " users\n\n";
    
    // Verify
    echo "=== VERIFICATION ===\n";
    $stmt = $pdo->query("SELECT id, email, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $ok = password_verify('password', $u['password']) ? '✓' : '✗';
        echo "$ok ID:{$u['id']} {$u['email']}\n";
    }
    
    // Check campuses and schedules
    $campuses = $pdo->query("SELECT COUNT(*) FROM campuses")->fetchColumn();
    $schedules = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
    echo "\n=== COUNTS ===\n";
    echo "Campuses: $campuses\n";
    echo "Schedules: $schedules\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
