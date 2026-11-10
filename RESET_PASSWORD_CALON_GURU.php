<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$hashed = password_hash('CalonGuru2026!', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password=? WHERE kelas='CALON-GURU' AND role='siswa'");
$stmt->execute([$hashed]);

echo "<p style='font-family:sans-serif;color:green'>✅ Password {$stmt->rowCount()} akun CALON-GURU direset ke <b>CalonGuru2026!</b></p>";
