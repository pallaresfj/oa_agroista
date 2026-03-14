<?php

$host = '127.0.0.1';
$dbname = 'laravel_teaching_assistance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== RUNNING MIGRATIONS ===\n\n";

    // 1. Add fields to users table
    echo "1. Adding fields to users table...\n";
    try {
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN role VARCHAR(20) DEFAULT 'docente' AFTER password,
            ADD COLUMN phone VARCHAR(20) NULL AFTER role,
            ADD COLUMN identification_number VARCHAR(50) NULL AFTER phone,
            ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER identification_number
        ");
        echo "   ✓ Users table updated\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   - Columns already exist\n";
        } else {
            throw $e;
        }
    }

    // 2. Create campuses table
    echo "\n2. Creating campuses table...\n";
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS campuses (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            address TEXT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            radius_meters INT UNSIGNED DEFAULT 100,
            qr_code_path VARCHAR(255) NULL,
            qr_token VARCHAR(64) NULL UNIQUE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    echo "   ✓ Campuses table created\n";

    // 3. Create schedules table
    echo "\n3. Creating schedules table...\n";
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS schedules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            campus_id BIGINT UNSIGNED NOT NULL,
            day_of_week TINYINT UNSIGNED NOT NULL,
            check_in_time TIME NOT NULL,
            check_out_time TIME NOT NULL,
            tolerance_minutes INT UNSIGNED DEFAULT 15,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    echo "   ✓ Schedules table created\n";

    // 4. Create attendances table
    echo "\n4. Creating attendances table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendances (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            campus_id BIGINT UNSIGNED NOT NULL,
            schedule_id BIGINT UNSIGNED NULL,
            date DATE NOT NULL,
            check_in_time DATETIME NULL,
            check_out_time DATETIME NULL,
            status VARCHAR(20) DEFAULT 'pendiente',
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            distance_from_campus DECIMAL(10, 2) NULL,
            device_info JSON NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE CASCADE,
            FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Attendances table created\n";

    // 5. Record migrations
    echo "\n5. Recording migrations...\n";
    $migrations = [
        '2024_01_28_000001_create_campuses_table',
        '2024_01_28_000002_create_schedules_table',
        '2024_01_28_000003_create_attendances_table',
        '2024_01_28_000004_add_fields_to_users_table',
    ];
    foreach ($migrations as $mig) {
        try {
            $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('$mig', 2)");
        } catch (PDOException $e) {
            // Ignore duplicates
        }
    }
    echo "   ✓ Migrations recorded\n";

    echo "\n=== MIGRATIONS COMPLETE ===\n";

    // Show tables
    echo "\nTables in database:\n";
    $stmt = $pdo->query('SHOW TABLES');
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
        echo "- $t\n";
    }

} catch (PDOException $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}
