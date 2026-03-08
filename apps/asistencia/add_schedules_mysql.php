<?php
$host = '127.0.0.1';
$dbname = 'laravel_teaching_assistance';
$pdo = new PDO("mysql:host=$host;dbname=$dbname", 'root', '');

// Clear existing schedules and add new
$pdo->exec("DELETE FROM schedules");

$times = [
    ['07:00:00', '12:00:00'],
    ['08:00:00', '13:00:00'],
    ['14:00:00', '18:00:00'],
];

// Get docentes
$docentes = $pdo->query("SELECT id FROM users WHERE role = 'docente'")->fetchAll(PDO::FETCH_COLUMN);
$campuses = $pdo->query("SELECT id FROM campuses")->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("INSERT INTO schedules (user_id, campus_id, day_of_week, check_in_time, check_out_time, tolerance_minutes, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 15, 1, NOW(), NOW())");

$i = 0;
foreach ($docentes as $docId) {
    $campusId = $campuses[$i % count($campuses)];
    $time = $times[$i % count($times)];
    for ($day = 1; $day <= 5; $day++) {
        $stmt->execute([$docId, $campusId, $day, $time[0], $time[1]]);
    }
    $i++;
}

// Directivo schedule
$directivo = $pdo->query("SELECT id FROM users WHERE role = 'directivo'")->fetchColumn();
if ($directivo) {
    for ($day = 1; $day <= 5; $day++) {
        $stmt->execute([$directivo, $campuses[0], $day, '08:00:00', '17:00:00']);
    }
}

echo "Schedules created: " . $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn() . "\n";
