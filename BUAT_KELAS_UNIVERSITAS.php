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
| DATA SISWA UNIVERSITAS
|--------------------------------------------------------------------------
*/
$names = [
"Alfrido Bayu Sipayung",
"Anjuando Carnelian Simamora",
"Christian Felix Silalahi",
"Clarita Uli Sudiro S",
"Daud Sitanggang",
"Destine Elyona Panggabean",
"Ephron Kennedy Sitanggang",
"Grace Cecilia Sihaloho",
"Gracia Angelia Sihaloho",
"Hikaru Margarette Sitepu",
"Jacinda Lasniroha Marito Hutapea",
"Jacob Trigautama Siringo",
"Jems Joustra Ginting",
"Joano Armana Gantang M Tarigan",
"Keisya Anggita Br Sembiring",
"Martha Dhea Equita Ginting",
"Michael Aaron Hutagaol",
"Pilippi Dewantara Situmeang",
"Rama Marcel Timothy",
"Raphael Diovana Tarigan",
"Raphael Omasio Harefa",
"Rio Brandyo Septhian Saragih",
"Ruth Evelin Pasaribu",
"Ruza Eidel Sembiring",
"Sakti Judika P.D. Sianipar",
"Sinari Ilene Situmorang",
"Stafindo Tuah Purba",
"Tristan Samudra Manurung",
"Yoshe Janatap Putra Munthe"
];

/*
|--------------------------------------------------------------------------
| GENERATE USERNAME (SUKU KATA 1 & 2)
|--------------------------------------------------------------------------
*/
$siswa = [];

foreach ($names as $nama) {

    // ambil kata pertama & kedua
    $parts = explode(' ', strtolower($nama));

    $first  = preg_replace('/[^a-z]/', '', $parts[0] ?? '');
    $second = preg_replace('/[^a-z]/', '', $parts[1] ?? '');

    $username = $first . '.' . $second . '.univ@student.ubkn.sch.id';

    $siswa[] = [
        'nama' => $nama,
        'username' => $username
    ];
}

/*
|--------------------------------------------------------------------------
| CONFIG AKUN
|--------------------------------------------------------------------------
*/
$password = 'Siswa123!';
$hashed   = password_hash($password, PASSWORD_DEFAULT);
$kelas    = 'UNIVERSITAS';

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

echo "<h2>Buat Akun Siswa UNIVERSITAS</h2>";
echo "<table><tr><th>Nama</th><th>Username</th><th>Status</th></tr>";

foreach ($siswa as $s) {

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$s['username']]);

    if ($stmt->fetch()) {
        echo "<tr>
            <td>{$s['nama']}</td>
            <td>{$s['username']}</td>
            <td class='skip'>⚠️ Sudah ada</td>
        </tr>";
        continue;
    }

    $pdo->prepare("
        INSERT INTO users (username, password, full_name, kelas, role, created_at)
        VALUES (?, ?, ?, ?, 'siswa', NOW())
    ")->execute([$s['username'], $hashed, $s['nama'], $kelas]);

    echo "<tr>
        <td>{$s['nama']}</td>
        <td>{$s['username']}</td>
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