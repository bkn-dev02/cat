<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$pdo->prepare("UPDATE users SET kelas='X-TESLA' WHERE username='yohannes.felix@siswa.ubkn.sch.id'")
    ->execute();

echo "<p style='font-family:sans-serif;color:green'>✅ Kelas Yohannes Felix Tamba diupdate ke <b>X-TESLA</b></p>";
