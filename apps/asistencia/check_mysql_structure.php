<?php
$host = '127.0.0.1';
$dbname = 'laravel_teaching_assistance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TABLES IN $dbname ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "- $t\n";
    }
    
    echo "\n=== USERS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "{$c['Field']} | {$c['Type']} | {$c['Null']}\n";
    }
    
    echo "\n=== MIGRATIONS TABLE ===\n";
    $stmt = $pdo->query("SELECT migration, batch FROM migrations ORDER BY batch, migration");
    $migs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($migs as $m) {
        echo "[{$m['batch']}] {$m['migration']}\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
