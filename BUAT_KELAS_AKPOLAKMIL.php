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
| DATA SISWA (HANYA NAMA)
|--------------------------------------------------------------------------
*/
$siswa = [
    "Abraham Edgar Hutauruk",
    "Amsal Cristian Bagariang",
    "Andraw Matthew Panjaitan",
    "Andrew Rafael Hutabarat",
    "Arvendo Yosepin Sinaga",
    "Bere Iting Nataliya Br Sitepu",
    "Choky Jordan Saragih",
    "Daniel Evan Siahaan",
    "Davin Halomoan Siagian",
    "Erastus Enu Putranta Sinukaban",
    "George Fredrick Berth Ginting",
    "Ivander Vania Marbun",
    "Janice Melosa Siahaan",
    "Jansen Afliandi Manik",
    "Josep Halomoan Simatupang",
    "Jovan Felix Alvaro Sitorus",
    "Mario Lado Sinurat",
    "Michael Yoseph Barus",
    "Oliver Leo Jackson Lumbantobing",
    "Raditya Pratama Karo Karo",
    "Sutan Alexander Helios Aritonang"
];

$password = 'Siswa123!';
$hashed   = password_hash($password, PASSWORD_DEFAULT);
$kelas    = 'AKPOL-AKMIL';

/*
|--------------------------------------------------------------------------
| UI
|--------------------------------------------------------------------------
*/
echo "<style>
body{font-family:sans-serif;padding:20px;max-width:800px;margin:auto}
table{border-collapse:collapse;width:100%}
td,th{border:1px solid #e2e8f0;padding:10px}
th{background:#f1f5f9}
.ok{color:green}
.skip{color:orange}
</style>";

echo "<h2>Buat Akun Siswa AKPOL / AKMIL</h2>";
echo "<table><tr><th>Nama</th><th>Username</th><th>Status</th></tr>";

/*
|--------------------------------------------------------------------------
| LOOP
|--------------------------------------------------------------------------
*/
foreach ($siswa as $nama) {

    // pecah nama
    $parts = explode(' ', strtolower(trim($nama)));

    // ambil kata pertama & kedua
    $first  = $parts[0] ?? '';
    $second = $parts[1] ?? '';

    // generate username
    $username = $first . '.' . $second . '.ak@student.ubkn.sch.id';

    // cek existing
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

    // insert
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
<b>Login URL:</b> cat-enterprise.binakasihnusantara.sch.id/auth/login
</div>";
?>