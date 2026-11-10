<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$calonGuru = [
    ['nama' => 'Blasita K. Sinaga',           'mapel' => 'TPA',   'username' => 'blasita.sinaga@calonstaffbkn.sch.id'],
    ['nama' => 'Adinda Dhita Syahputri',    'mapel' => 'TPA',   'username' => 'adinda.dhita@calonstaffbkn.sch.id'],
    ['nama' => 'Diah Ayu Permata',          'mapel' => 'TPA',       'username' => 'diah.permata@calonstaffbkn.sch.id'],
    ['nama' => 'Benyamin Ginting',          'mapel' => 'Matematika',       'username' => 'benyamin.ginting@calonguruubkn.sch.id'],
    ['nama' => 'Miko F. Silaban',          'mapel' => 'B.Inggris',       'username' => 'mikoa.silaban@calonguruubkn.sch.id'],
    ['nama' => 'Tonggo M. Tamba',          'mapel' => 'Fisika',       'username' => 'tonggo.tamba@calonguruubkn.sch.id'],
    ['nama' => 'Kampon Rumahorbo',          'mapel' => 'B. Inggris',       'username' => 'kampon.rumahorbo@calonguruubkn.sch.id'],
    ['nama' => 'Eddy Kelana Bangun',          'mapel' => 'Matematika',       'username' => 'eddy.bangun@calonguruubkn.sch.id'],
    ['nama' => 'Junianti Nababan',          'mapel' => 'Fisika',       'username' => 'junianti.nababan@calonguruubkn.sch.id'],
    ['nama' => 'Immanuel Unedo Pasaribu',          'mapel' => 'TPA',       'username' => 'immanuel.pasaribu@calonguruubkn.sch.id'],
    ['nama' => 'Mulia Lasniroha',          'mapel' => 'TPA',       'username' => 'mulia.lasniroha@calonstaffbkn.sch.id'],
    ['nama' => 'Fenisia',          'mapel' => 'TPA',       'username' => 'fenisia@calonstaffbkn.sch.id'],
    ['nama' => 'Conny Tarigan',          'mapel' => 'TPA',       'username' => 'conny@calonstaffbkn.sch.id'],
    ['nama' => 'Teuku',          'mapel' => 'TPA',       'username' => 'teuku@calonstaffbkn.sch.id'],
];

$password = 'CalonGuruStaff2026!';
$hashed   = password_hash($password, PASSWORD_DEFAULT);
$kelas    = 'CALON-GURU';

echo "<style>body{font-family:sans-serif;padding:20px;max-width:700px;margin:auto}
table{border-collapse:collapse;width:100%}td,th{border:1px solid #e2e8f0;padding:10px}
th{background:#f1f5f9}.ok{color:green}.skip{color:orange}</style>";
echo "<h2>Buat Akun Calon Guru — Hari 2 (Senin)</h2>";
echo "<table><tr><th>#</th><th>Nama</th><th>Mapel</th><th>Username</th><th>Status</th></tr>";

foreach ($calonGuru as $i => $cg) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$cg['username']]);
    if ($stmt->fetch()) {
        echo "<tr><td>".($i+1)."</td><td>{$cg['nama']}</td><td>{$cg['mapel']}</td><td>{$cg['username']}</td><td class='skip'>⚠️ Sudah ada</td></tr>";
        continue;
    }
    $pdo->prepare("INSERT INTO users (username, password, full_name, kelas, role, created_at) VALUES (?,?,?,?,'siswa',NOW())")
        ->execute([$cg['username'], $hashed, $cg['nama'], $kelas]);
    echo "<tr><td>".($i+1)."</td><td>{$cg['nama']}</td><td>{$cg['mapel']}</td><td>{$cg['username']}</td><td class='ok'>✅ Dibuat</td></tr>";
}

echo "</table>";
echo "<div style='margin-top:20px;background:#eff6ff;padding:16px;border-radius:8px'>";
echo "<b>Password semua akun:</b> <code>CalonGuruStaff2026!</code><br>";
echo "<b>Kelas:</b> CALON-GURU<br>";
echo "<b>Login URL:</b> cat-enterprise.binakasihnusantara.sch.id/auth/login";
echo "</div>";
echo "<p style='color:#64748b;margin-top:12px'>Selanjutnya: buat ujian test calon guru, lalu di Edit Ujian pilih <b>Kelas Tertentu Saja → CALON-GURU</b></p>";