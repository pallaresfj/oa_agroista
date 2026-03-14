<?php

require __DIR__.'/vendor/autoload.php';

// Load .env manually to verify
$envFile = file_get_contents(__DIR__.'/.env');
preg_match('/DB_DATABASE=(.*)/', $envFile, $matches);
echo '.env DB_DATABASE value: '.trim($matches[1] ?? 'NOT FOUND')."\n";

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Laravel config('database.connections.mysql.database'): ".config('database.connections.mysql.database')."\n";
echo "Laravel config('database.default'): ".config('database.default')."\n";

use Illuminate\Support\Facades\DB;

echo 'Actual DB name from query: '.DB::select('SELECT DATABASE() as db')[0]->db."\n";
