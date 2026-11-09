<?php

$config = require __DIR__ . '/config.production.php';

// ─── Data Calon Siswa ─────────────────────────────────────────────────────────

$kelas = 'CSR';

$calonSiswa = [
    ['nama' => 'Charen Aurel Christian Simarmata',    'username' => 'charen.simarmata@binakasihnusantara.sch.id'],
    ['nama' => 'Rehya Nataline Br Tarigan',           'username' => 'rehya.tarigan@binakasihnusantara.sch.id'],
    ['nama' => 'Roderick Christian Tarigan',          'username' => 'roderick.tarigan@binakasihnusantara.sch.id'],
    ['nama' => 'Ivana Artanti Br Ginting',            'username' => 'ivana.ginting@binakasihnusantara.sch.id'],
    ['nama' => 'Abeltsazar Tarigan Tua',              'username' => 'abeltsazar.tarigan@binakasihnusantara.sch.id'],
    ['nama' => 'Naeltra Almanda Barus',               'username' => 'naeltra.barus@binakasihnusantara.sch.id'],
    ['nama' => 'Wahid Maulana Karo Karo',             'username' => 'wahid.karokaro@binakasihnusantara.sch.id'],
    ['nama' => 'Dwita Febina Br Meliala',             'username' => 'dwita.meliala@binakasihnusantara.sch.id'],
    ['nama' => 'Rayhan Febriardine Hutagalung',       'username' => 'rayhan.hutagalung@binakasihnusantara.sch.id'],
    ['nama' => 'Jestisha Naomi Br Ginting',           'username' => 'jestisha.ginting@binakasihnusantara.sch.id'],
    ['nama' => 'Aurelia Stefany Br Sitepu',           'username' => 'aurelia.sitepu@binakasihnusantara.sch.id'],
    ['nama' => 'Melpin Rafael Gurusinga',             'username' => 'melpin.gurusinga@binakasihnusantara.sch.id'],
    ['nama' => 'Gisella Krishana Br Bangun',          'username' => 'gisella.bangun@binakasihnusantara.sch.id'],
    ['nama' => 'Hera Depiora Br Sembiring',           'username' => 'hera.sembiring@binakasihnusantara.sch.id'],
    ['nama' => 'Nadine Priscilia Br Sitepu',          'username' => 'nadine.sitepu@binakasihnusantara.sch.id'],
    ['nama' => 'Ema Natasya Br S. Depari',            'username' => 'ema.depari@binakasihnusantara.sch.id'],
    ['nama' => 'Oryzha Princesstha Perangin-angin',   'username' => 'oryzha.peranginangin@binakasihnusantara.sch.id'],
    ['nama' => 'Kesya Tridara Br Purba',              'username' => 'kesya.purba@binakasihnusantara.sch.id'],
    ['nama' => 'Nadzifah Arifah Harahap',             'username' => 'nadzifah.harahap@binakasihnusantara.sch.id'],
    ['nama' => 'Regina Febriani Ginting',             'username' => 'regina.ginting@binakasihnusantara.sch.id'],
    ['nama' => 'Audry Queen Br Ginting',              'username' => 'audry.ginting@binakasihnusantara.sch.id'],
    ['nama' => 'Gavrielino Berlamb Arkina Sinulingga','username' => 'gavrielino.sinulingga@binakasihnusantara.sch.id'],
    ['nama' => 'Ari Vincentsius Ferrer Tarigan',      'username' => 'ari.tarigan@binakasihnusantara.sch.id'],
    ['nama' => 'Mey Threzia Br Simamora',             'username' => 'mey.simamora@binakasihnusantara.sch.id'],
    ['nama' => 'Gwen Pregiera Somara Br Saragih',     'username' => 'gwen.saragih@binakasihnusantara.sch.id'],
    ['nama' => 'Yael Sevania Br Nainggolan',          'username' => 'yael.nainggolan@binakasihnusantara.sch.id'],
    ['nama' => 'Olivia Br Ginting',                   'username' => 'olivia.ginting@binakasihnusantara.sch.id'],
    ['nama' => 'Edrey Aloysius Tarigan',              'username' => 'edrey.tarigan@binakasihnusantara.sch.id'],
    ['nama' => 'Namora Ybrena Tarigan',               'username' => 'namora.tarigan@binakasihnusantara.sch.id'],
    ['nama' => 'Arga Basana Sagala',                  'username' => 'arga.sagala@binakasihnusantara.sch.id'],
    ['nama' => 'Valentina Br Sembiring',              'username' => 'valentina.sembiring@binakasihnusantara.sch.id'],
    ['nama' => 'Cecilia Mahaesa Br Sinukaban',        'username' => 'cecilia.sinukaban@binakasihnusantara.sch.id'],
    ['nama' => 'Yeriko Davrian Davrianti Pinem',      'username' => 'yeriko.pinem@binakasihnusantara.sch.id'],
    ['nama' => 'Griselda Bangun',                     'username' => 'griselda.bangun@binakasihnusantara.sch.id'],
    ['nama' => 'Risti Mei Morintha Br Tarigan',       'username' => 'risti.mei@binakasihnusantara.sch.id'],
];

$password = 'Ppdb2026!';

// ─── Koneksi Database ─────────────────────────────────────────────────────────

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // ─── Ambil daftar exam PPDB sekali saja ───────────────────────────────────

    $exams = $pdo->query("
        SELECT id FROM exams
        WHERE category = 'ppdb' AND status = 'published'
    ")->fetchAll(PDO::FETCH_COLUMN);

    $stmtInsertUser = $pdo->prepare("
        INSERT INTO users (username, password, full_name, kelas, role, created_at)
        VALUES (?, ?, ?, ?, 'siswa', NOW())
    ");

    $stmtAssignExam = $pdo->prepare("
        INSERT IGNORE INTO exam_participants (exam_id, user_id)
        VALUES (?, ?)
    ");

    $stmtCekUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // ─── Output HTML ──────────────────────────────────────────────────────────

    echo '<style>
        body   { font-family: sans-serif; padding: 20px; max-width: 750px; margin: auto; }
        table  { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        td, th { border: 1px solid #e2e8f0; padding: 10px; }
        th     { background: #f1f5f9; }
        .ok    { color: green; }
        .skip  { color: orange; }
    </style>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Kelas</th>
                <th>Exam Assigned</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

    // ─── Proses Setiap Siswa ──────────────────────────────────────────────────

    foreach ($calonSiswa as $i => $cs) {
        $no       = $i + 1;
        $nama     = htmlspecialchars($cs['nama']);
        $username = htmlspecialchars($cs['username']);

        $stmtCekUser->execute([$cs['username']]);

        if ($stmtCekUser->fetch()) {
            echo "<tr>
                <td>$no</td>
                <td>$nama</td>
                <td>$username</td>
                <td>$kelas</td>
                <td>-</td>
                <td class='skip'>⚠️ Sudah ada</td>
            </tr>";
            continue;
        }

        $stmtInsertUser->execute([$cs['username'], $hashed, $cs['nama'], $kelas]);
        $userId = $pdo->lastInsertId();

        foreach ($exams as $examId) {
            $stmtAssignExam->execute([$examId, $userId]);
        }

        echo "<tr>
                <td>$no</td>
                <td>$nama</td>
                <td>$username</td>
                <td>$kelas</td>
                <td>" . count($exams) . " ujian</td>
                <td class='ok'>✅ Dibuat</td>
            </tr>";
    }

    // ─── Footer ───────────────────────────────────────────────────────────────

    echo "  </tbody>
        </table>
        <div style='background:#eff6ff;padding:16px;border-radius:8px;'>
            <b>Password semua akun:</b> <code>$password</code><br>
            <b>Kelas:</b> $kelas<br>
            <b>Login URL:</b> cat-enterprise.binakasihnusantara.sch.id/auth/login
        </div>";

} catch (PDOException $e) {
    echo "<p style='color:red;font-family:sans-serif'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}