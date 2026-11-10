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
| DATA SISWA KEDINASAN-2
|--------------------------------------------------------------------------
*/
$siswa = [
    "A. Bestma Sitepu",
    "Agave Ahav Sipayung",
    "Aran Jonathan Tamba",
    "Aurel Felia",
    "Aurelia Brigita Tarigan",
    "Christian Valentino Tarigan",
    "Clara Rindu Novita Situmeang",
    "Dea Yohebet Sitohang",
    "Delsa Putri Qanaya Br Tarigan",
    "Glorya V.T Siahaan",
    "Herbert Robertus I Habeahan",
    "Hot Karenhapukh P Nainggolan",
    "Ingrid Richela",
    "Isa Putri Utama Sihombing",
    "Khanza Stefany Siahaan",
    "Natasha Mutiara Indri Br Sihite",
    "Neila Elizeba Butarbutar",
    "Noreen Aloina Joyo Sirait",
    "Octaviani Cecilya Damanik",
    "Oswellov Hutabarat",
    "Pilemon Dewantoro Situmeang",
    "Raja Albert Pangihutan Sitanggang",
    "Reggie Atmaja Siboro",
    "Sheera Ivana M Lumbantobing",
    "Trinity Neo Julita",
    "Wira Dian Mitra Nainggolan",
    "Yan Okuli Bramada Lingga",
    "Yokhebed Sugiantono Siahaan"
];

$password = 'Siswa123!';
$hashed   = password_hash($password, PASSWORD_DEFAULT);
$kelas    = 'KEDINASAN-2';

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

echo "<h2>Buat Akun Siswa KEDINASAN-2</h2>";
echo "<table><tr><th>Nama</th><th>Username</th><th>Status</th></tr>";

/*
|--------------------------------------------------------------------------
| LOOP
|--------------------------------------------------------------------------
*/
foreach ($siswa as $nama) {

    $parts = explode(' ', strtolower(trim($nama)));

    // bersihin karakter aneh (titik, dll)
    $first  = preg_replace('/[^a-z]/', '', $parts[0] ?? '');
    $second = preg_replace('/[^a-z]/', '', $parts[1] ?? 'user');

    $username = $first . '.' . $second . '.kd2@student.ubkn.sch.id';

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
<b>Login URL:</b> cat-enterprise.binakasihnusantara.sch.id/auth/login
</div>";
?>