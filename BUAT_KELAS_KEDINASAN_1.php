<?php
$config = require __DIR__ . '/config.production.php';

$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'],
    $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/*
|--------------------------------------------------------------------------
| DATA SISWA KEDINASAN-1
|--------------------------------------------------------------------------
*/
$siswa = [
    "Adam Putra Pratama Harahap",
    "Adriano Bremana Barus",
    "Alfredo Qeenar Agustian",
    "Arvin Saut Parulian Sihotang",
    "Boyd Nathanael Sirait",
    "Bungaran Sembiring",
    "Dhevyn Gamaliel Nababan",
    "Eikel Ananda Sitepu",
    "El Yosia RM Sinaga",
    "Farhan Gabrielle Sitepu",
    "G. Phaska",
    "Gabriel Pandiangan",
    "Galileo Levinas P Simanungkalit",
    "Glenn Davin Saragih",
    "Hanukah Glacio Permana Purba",
    "Henderson Rivai Siahaan",
    "Jeremy Alpha Cantino Siregar",
    "Jore Ananta Surbakti",
    "Maranatha Margaretha Naibaho",
    "Octavia Bunga S Simamora",
    "Rafa Radinka Sitepu",
    "Rafa Sahmadear Saragih",
    "Rafael Cristiano Simarmata",
    "Raka Fithersan Sembiring",
    "Ralph Vaughan",
    "Sandiaga Krissyan Uno Ginting",
    "Sigus Wilson Sinaga",
    "Sion Anugrah Sianturi",
    "Yehezkiel Silitonga",
    "Yoel Pranata Ginting",
    "Yohanes Farel Napitu",
    "Zefanya Arpanius Sinaga"
];

$password = 'Siswa123!';
$hashed   = password_hash($password, PASSWORD_DEFAULT);
$kelas    = 'KEDINASAN-1';

/*
|--------------------------------------------------------------------------
| UI
|--------------------------------------------------------------------------
*/
echo "<style>
body{font-family:sans-serif;padding:20px;max-width:900px;margin:auto}
table{border-collapse:collapse;width:100%}
td,th{border:1px solid #e2e8f0;padding:10px}
th{background:#f1f5f9}
.ok{color:green}
.skip{color:orange}
</style>";

echo "<h2>Buat Akun Siswa KEDINASAN-1</h2>";
echo "<table><tr><th>Nama</th><th>Username</th><th>Status</th></tr>";

/*
|--------------------------------------------------------------------------
| LOOP
|--------------------------------------------------------------------------
*/
foreach ($siswa as $nama) {

    $parts = explode(' ', strtolower(trim($nama)));

    $first  = preg_replace('/[^a-z]/', '', $parts[0] ?? '');
    $second = preg_replace('/[^a-z]/', '', $parts[1] ?? 'user');

    $username = $first . '.' . $second . '.kd1@student.ubkn.sch.id';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        echo "<tr>
            <td>$nama</td>
            <td>$username</td>
            <td class='skip'>⚠️ Sudah ada</td>
        </tr>";
        continue;
    }

    $pdo->prepare("
        INSERT INTO users (username, password, full_name, kelas, role, created_at)
        VALUES (?, ?, ?, ?, 'siswa', NOW())
    ")->execute([$username, $hashed, $nama, $kelas]);

    echo "<tr>
        <td>$nama</td>
        <td>$username</td>
        <td class='ok'>✅ Dibuat</td>
    </tr>";
}

echo "</table>";

echo "<div style='margin-top:20px;background:#eff6ff;padding:16px;border-radius:8px'>
<b>Password semua akun:</b> <code>$password</code><br>
<b>Kelas:</b> $kelas<br>
<b>Format Email:</b> nama.depan.kd1@student.ubkn.sch.id<br>
<b>Login URL:</b> cat-enterprise.binakasihnusantara.sch.id/auth/login
</div>";
?>