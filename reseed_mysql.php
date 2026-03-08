<?php
$host = '127.0.0.1';
$dbname = 'laravel_teaching_assistance';
$pdo = new PDO("mysql:host=$host;dbname=$dbname", 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== CLEARING DATABASE ===\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE attendances");
$pdo->exec("TRUNCATE TABLE schedules");
$pdo->exec("TRUNCATE TABLE campuses");
$pdo->exec("TRUNCATE TABLE users");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "✓ All tables cleared\n\n";

echo "=== SEEDING NEW DATA ===\n";
$hash = password_hash('pass1234', PASSWORD_BCRYPT, ['cost' => 12]);

// Users
$users = [
    ['Admin Soporte', 'soporte@teachingassistance.com', 'soporte', '3001234567', '1234567890'],
    ['Juan Directivo', 'directivo@teachingassistance.com', 'directivo', '3009876543', '0987654321'],
    ['Docente 1', 'docente1@teachingassistance.com', 'docente', '3001110001', '1112223331'],
    ['Docente 2', 'docente2@teachingassistance.com', 'docente', '3001110002', '1112223332'],
    ['Docente 3', 'docente3@teachingassistance.com', 'docente', '3001110003', '1112223333'],
    ['Docente 4', 'docente4@teachingassistance.com', 'docente', '3001110004', '1112223334'],
    ['Docente 5', 'docente5@teachingassistance.com', 'docente', '3001110005', '1112223335'],
];

$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, identification_number, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
foreach ($users as $u) {
    $stmt->execute([$u[0], $u[1], $hash, $u[2], $u[3], $u[4]]);
    echo "✓ Created user: {$u[0]}\n";
}

// Campuses
$campuses = [
    ['Sede Norte', 'Calle 100 #15-20, Bogotá', 4.7110, -74.0721, 100],
    ['Sede Sur', 'Carrera 30 #45-67, Bogotá', 4.5981, -74.0758, 100],
    ['Sede Centro', 'Avenida Jiménez #10-25, Bogotá', 4.6097, -74.0817, 80],
];

$stmt = $pdo->prepare("INSERT INTO campuses (name, address, latitude, longitude, radius_meters, qr_token, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
foreach ($campuses as $c) {
    $stmt->execute([$c[0], $c[1], $c[2], $c[3], $c[4], bin2hex(random_bytes(16))]);
    echo "✓ Created campus: {$c[0]}\n";
}

// Schedules
$times = [
    ['07:00:00', '12:00:00'],
    ['08:00:00', '13:00:00'],
    ['14:00:00', '18:00:00'],
];

$docentes = $pdo->query("SELECT id FROM users WHERE role = 'docente'")->fetchAll(PDO::FETCH_COLUMN);
$campusIds = $pdo->query("SELECT id FROM campuses")->fetchAll(PDO::FETCH_COLUMN);
$schedStmt = $pdo->prepare("INSERT INTO schedules (user_id, campus_id, day_of_week, check_in_time, check_out_time, tolerance_minutes, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 15, 1, NOW(), NOW())");

$i = 0;
foreach ($docentes as $docId) {
    $campusId = $campusIds[$i % count($campusIds)];
    $time = $times[$i % count($times)];
    for ($day = 1; $day <= 5; $day++) {
        $schedStmt->execute([$docId, $campusId, $day, $time[0], $time[1]]);
    }
    $i++;
}

// Directivo schedule
$directivo = $pdo->query("SELECT id FROM users WHERE role = 'directivo'")->fetchColumn();
if ($directivo) {
    for ($day = 1; $day <= 5; $day++) {
        $schedStmt->execute([$directivo, $campusIds[0], $day, '08:00:00', '17:00:00']);
    }
}
echo "✓ Created schedules\n";

// Summary
echo "\n=== SUMMARY ===\n";
echo "Users: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
echo "Campuses: " . $pdo->query("SELECT COUNT(*) FROM campuses")->fetchColumn() . "\n";
echo "Schedules: " . $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn() . "\n";

// Verify password
echo "\n=== CREDENTIALS ===\n";
echo "soporte@teachingassistance.com / pass1234\n";
echo "directivo@teachingassistance.com / pass1234\n";
echo "docente1@teachingassistance.com / pass1234\n";
