<?php
$host = '127.0.0.1';
$dbname = 'laravel_teaching_assistance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);
    
    echo "=== SEEDING DATABASE ===\n\n";
    
    // Check if users already exist
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    if ($count == 0) {
        echo "Creating users...\n";
        // Create users
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
            echo "  ✓ Created {$u[0]}\n";
        }
    } else {
        echo "Users exist ($count), updating passwords...\n";
        $pdo->prepare("UPDATE users SET password = ?")->execute([$hash]);
        
        // Ensure role is set
        $pdo->exec("UPDATE users SET role = 'soporte' WHERE email = 'soporte@teachingassistance.com'");
        $pdo->exec("UPDATE users SET role = 'directivo' WHERE email = 'directivo@teachingassistance.com'");
        $pdo->exec("UPDATE users SET role = 'docente' WHERE email LIKE 'docente%'");
        $pdo->exec("UPDATE users SET is_active = 1");
        echo "  ✓ Passwords and roles updated\n";
    }
    
    // Create campuses
    echo "\nCreating campuses...\n";
    $campuses = [
        ['Sede Norte', 'Calle 100 #15-20, Bogotá', 4.7110, -74.0721, 100, bin2hex(random_bytes(16))],
        ['Sede Sur', 'Carrera 30 #45-67, Bogotá', 4.5981, -74.0758, 100, bin2hex(random_bytes(16))],
        ['Sede Centro', 'Avenida Jiménez #10-25, Bogotá', 4.6097, -74.0817, 80, bin2hex(random_bytes(16))],
    ];
    
    foreach ($campuses as $c) {
        $exists = $pdo->prepare("SELECT id FROM campuses WHERE name = ?");
        $exists->execute([$c[0]]);
        if (!$exists->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO campuses (name, address, latitude, longitude, radius_meters, qr_token, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $c[5]]);
            echo "  ✓ Created {$c[0]}\n";
        } else {
            echo "  - {$c[0]} already exists\n";
        }
    }
    
    // Create schedules
    echo "\nCreating schedules...\n";
    $scheduleCount = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
    if ($scheduleCount == 0) {
        $times = [
            ['07:00:00', '12:00:00'],
            ['08:00:00', '13:00:00'],
            ['14:00:00', '18:00:00'],
        ];
        
        // Get user IDs
        $stmt = $pdo->query("SELECT id, email FROM users WHERE role = 'docente' LIMIT 5");
        $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get campus IDs
        $campusIds = $pdo->query("SELECT id FROM campuses")->fetchAll(PDO::FETCH_COLUMN);
        
        $schedStmt = $pdo->prepare("INSERT INTO schedules (user_id, campus_id, day_of_week, check_in_time, check_out_time, tolerance_minutes, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 15, 1, NOW(), NOW())");
        
        $i = 0;
        foreach ($docentes as $doc) {
            $campusId = $campusIds[$i % count($campusIds)];
            $time = $times[$i % count($times)];
            for ($day = 1; $day <= 5; $day++) {
                $schedStmt->execute([$doc['id'], $campusId, $day, $time[0], $time[1]]);
            }
            $i++;
        }
        
        // Add schedule for directivo
        $directivo = $pdo->query("SELECT id FROM users WHERE role = 'directivo'")->fetch();
        if ($directivo) {
            for ($day = 1; $day <= 5; $day++) {
                $schedStmt->execute([$directivo['id'], $campusIds[0], $day, '08:00:00', '17:00:00']);
            }
        }
        
        echo "  ✓ Created schedules\n";
    } else {
        echo "  - Schedules already exist ($scheduleCount)\n";
    }
    
    // Summary
    echo "\n=== SUMMARY ===\n";
    echo "Users: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
    echo "Campuses: " . $pdo->query("SELECT COUNT(*) FROM campuses")->fetchColumn() . "\n";
    echo "Schedules: " . $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn() . "\n";
    
    echo "\n=== USER CREDENTIALS ===\n";
    echo "soporte@teachingassistance.com / password\n";
    echo "directivo@teachingassistance.com / password\n";
    echo "docente1@teachingassistance.com / password\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
