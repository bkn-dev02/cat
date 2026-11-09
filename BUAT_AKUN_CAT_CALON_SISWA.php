<?php
$config = require __DIR__ . '/config.production.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $username = 'kelvinsitorus381@gmail.com';
    $nama     = 'Kelvin Nauli Sitorus';
    $password = 'Siswa123!';
    $kelas    = 'PPDB-REGULER';

    // Cek apakah user sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        echo "<p style='color:orange;font-family:sans-serif'>
                ⚠️ Akun sudah ada. Tidak perlu dibuat ulang.
              </p>";
        exit;
    }

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $insertUser = $pdo->prepare("
        INSERT INTO users (username, password, full_name, kelas, role, created_at) 
        VALUES (?, ?, ?, ?, 'siswa', NOW())
    ");
    $insertUser->execute([$username, $hashed, $nama, $kelas]);

    // Ambil user ID terakhir
    $userId = $pdo->lastInsertId();

    // Ambil daftar exam
    $exams = $pdo->query("
        SELECT id FROM exams 
        WHERE category='ppdb' AND status='published'
    ")->fetchAll(PDO::FETCH_COLUMN);

    // Assign ke exam
    $ins = $pdo->prepare("
        INSERT IGNORE INTO exam_participants (exam_id, user_id) 
        VALUES (?, ?)
    ");

    foreach ($exams as $examId) {
        $ins->execute([$examId, $userId]);
    }

    // Output sukses
    echo "<div style='font-family:sans-serif;max-width:400px;margin:40px auto;padding:20px;background:#d1fae5;border-radius:8px'>";
    echo "<h3 style='color:#065f46'>✅ Akun berhasil dibuat!</h3>";
    echo "<p><b>Nama:</b> $nama</p>";
    echo "<p><b>Username:</b> <code>$username</code></p>";
    echo "<p><b>Password:</b> <code>$password</code></p>";
    echo "<p><b>Kelas:</b> $kelas</p>";
    echo "<p>Sudah di-assign ke " . count($exams) . " ujian PPDB.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color:red;font-family:sans-serif'>
            ❌ Error: " . $e->getMessage() . "
          </p>";
}
?>