<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
    $config['db_user'],
    $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$defaultPassword = 'staff123';

$users = [
    ['full_name' => 'Yustinus Bukit S.T., M.T',           'username' => 'yustinus@smabkn.sch.id'],
    ['full_name' => 'Erry Tarigan S.Pd., M.Pd',            'username' => 'erry@smabkn.sch.id'],
    ['full_name' => 'Drs. Erlanses Sitorus',                'username' => 'erlanses@smabkn.sch.id'],
    ['full_name' => 'Benny Isura Kerina Ginting A.Md',      'username' => 'benny@smabkn.sch.id'],
    ['full_name' => 'Elianser Sihombing S.S.I',             'username' => 'elianser@smabkn.sch.id'],
    ['full_name' => 'Wahyudi Damanik S.Kom',                'username' => 'wahyudi@smabkn.sch.id'],
    ['full_name' => 'Pieter Nero Ginting A.Md.,Ds',         'username' => 'pieter@smabkn.sch.id'],
    ['full_name' => 'Evayanti Banjarnahor S.Kom',           'username' => 'evayanti@smabkn.sch.id'],
    ['full_name' => 'Oktavianus Sihotang',                  'username' => 'oktavianus@smabkn.sch.id'],
    ['full_name' => 'Tri Hikmat Harefa S.Kom',              'username' => 'tri@smabkn.sch.id'],
    ['full_name' => 'Resalda Sihotang S.I.Kom',             'username' => 'resalda@smabkn.sch.id'],
    ['full_name' => 'Dory Anesti Br Sitepu S.Psi',          'username' => 'dory@smabkn.sch.id'],
    ['full_name' => 'Liony Patricia Panjaitan S.S',         'username' => 'liony@smabkn.sch.id'],
    ['full_name' => 'Oky Sihotang S.Kom',                   'username' => 'oky@smabkn.sch.id'],
    ['full_name' => 'Preddi Tua Marbun S.Kom',              'username' => 'preddi@smabkn.sch.id'],
    ['full_name' => 'Anisah Dwi Pratiwi A.Md., M',          'username' => 'anisah@smabkn.sch.id'],
    ['full_name' => 'Dea Ananda A.Md., AB',                 'username' => 'dea@smabkn.sch.id'],
];

$stmtCheck  = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmtInsert = $pdo->prepare("INSERT INTO users (username, password, full_name, kelas, role, created_at) VALUES (?, ?, ?, '', 'staff', NOW())");

echo "<div style='font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px'>";
echo "<h2 style='color:#1e3a5f'>📋 Import Akun Staff — SMABKN</h2>";
echo "<table style='width:100%;border-collapse:collapse;font-size:14px'>";
echo "<thead><tr style='background:#1e3a5f;color:#fff'>
        <th style='padding:8px 12px;text-align:left'>No</th>
        <th style='padding:8px 12px;text-align:left'>Nama</th>
        <th style='padding:8px 12px;text-align:left'>Username</th>
        <th style='padding:8px 12px;text-align:left'>Password</th>
        <th style='padding:8px 12px;text-align:left'>Status</th>
      </tr></thead><tbody>";

$created = 0;
$skipped = 0;

foreach ($users as $i => $user) {
    $stmtCheck->execute([$user['username']]);
    $rowBg = ($i % 2 === 0) ? '#f9fafb' : '#ffffff';

    if ($stmtCheck->fetch()) {
        $skipped++;
        $status = "<span style='color:orange'>⚠️ Sudah ada</span>";
    } else {
        $hashed = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $stmtInsert->execute([$user['username'], $hashed, $user['full_name']]);
        $created++;
        $status = "<span style='color:#065f46'>✅ Berhasil</span>";
    }

    echo "<tr style='background:{$rowBg}'>
            <td style='padding:8px 12px'>" . ($i + 1) . "</td>
            <td style='padding:8px 12px'>{$user['full_name']}</td>
            <td style='padding:8px 12px'><code>{$user['username']}</code></td>
            <td style='padding:8px 12px'><code>{$defaultPassword}</code></td>
            <td style='padding:8px 12px'>{$status}</td>
          </tr>";
}

echo "</tbody></table>";
echo "<div style='margin-top:20px;padding:16px;background:#d1fae5;border-radius:8px;color:#065f46'>
        <b>Ringkasan:</b> {$created} akun berhasil dibuat, {$skipped} akun dilewati (sudah ada).
      </div>";
echo "</div>";
?>