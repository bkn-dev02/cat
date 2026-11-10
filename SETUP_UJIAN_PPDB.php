<?php
/**
 * Setup Ujian Seleksi PPDB - 2 Sesi
 * Jalankan SEKALI, lalu hapus file ini!
 */
$config = require __DIR__ . '/config.production.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->beginTransaction();

    // =============================================
    // 1. INSERT SUBJECTS PPDB (jika belum ada)
    // =============================================
    $subjects = [
        'Matematika PPDB'   => 'Matematika - Ujian Seleksi Masuk PPDB',
        'IPA PPDB'          => 'Ilmu Pengetahuan Alam - Ujian Seleksi Masuk PPDB',
        'IPS PPDB'          => 'Ilmu Pengetahuan Sosial - Ujian Seleksi Masuk PPDB',
        'Bahasa PPDB'       => 'Bahasa Indonesia & Inggris - Ujian Seleksi Masuk PPDB',
        'TPS PPDB'          => 'Tes Potensi Skolastik - Ujian Seleksi Masuk PPDB',
    ];

    $subjectIds = [];
    foreach ($subjects as $name => $desc) {
        // Cek sudah ada belum
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE name = ?");
        $stmt->execute([$name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $subjectIds[$name] = $existing['id'];
            echo "Subject <b>$name</b> sudah ada (id: {$existing['id']})<br>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO subjects (name, description, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$name, $desc]);
            $subjectIds[$name] = $pdo->lastInsertId();
            echo "Subject <b>$name</b> dibuat (id: {$subjectIds[$name]})<br>";
        }
    }

    // =============================================
    // 2. BUAT UJIAN SESI I (Matematika + IPA)
    //    Total durasi: 90 + 60 = 150 menit
    //    Sesuaikan tanggal ujian di bawah!
    // =============================================
    $tanggalUjian = '2026-03-27'; // <-- GANTI TANGGAL UJIAN
    $sesi1Start   = $tanggalUjian . ' 08:00:00';
    $sesi1End     = $tanggalUjian . ' 10:30:00'; // 150 menit dari jam 08:00

    $stmt = $pdo->prepare("SELECT id FROM exams WHERE title = 'Ujian Seleksi Masuk PPDB - Sesi I'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<br><b style='color:orange'>Ujian Sesi I sudah ada, dilewati.</b><br>";
        $sesi1Id = null;
    } else {
        $token1 = 'PPDB-SESI1-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $stmt = $pdo->prepare("
            INSERT INTO exams (title, category, subject_id, duration, start_time, end_time, token, randomize_questions, exam_type, status, created_by, created_at)
            VALUES ('Ujian Seleksi Masuk PPDB - Sesi I', 'ppdb', NULL, 150, ?, ?, ?, 0, 'multi', 'draft', 1, NOW())
        ");
        $stmt->execute([$sesi1Start, $sesi1End, $token1]);
        $sesi1Id = $pdo->lastInsertId();
        echo "<br>Ujian <b>Sesi I</b> dibuat (id: $sesi1Id)<br>";

        // Tambah exam_subjects untuk Sesi I
        $sesi1Subjects = [
            ['Matematika PPDB', 90, 1],
            ['IPA PPDB',        60, 2],
        ];
        foreach ($sesi1Subjects as [$name, $duration, $order]) {
            $sid = $subjectIds[$name];
            $stmt = $pdo->prepare("INSERT INTO exam_subjects (exam_id, subject_id, order_num) VALUES (?, ?, ?)");
            $stmt->execute([$sesi1Id, $sid, $order]);
            echo "&nbsp;&nbsp;→ Subject <b>$name</b> ($duration menit) ditambahkan ke Sesi I<br>";
        }
    }

    // =============================================
    // 3. BUAT UJIAN SESI II (IPS + Bahasa + TPS)
    //    Total durasi: 60 + 50 + 45 = 155 menit
    //    Mulai 15 menit setelah Sesi I selesai
    // =============================================
    $sesi2Start = $tanggalUjian . ' 10:45:00'; // 15 menit istirahat setelah 10:30
    $sesi2End   = $tanggalUjian . ' 13:20:00'; // 155 menit dari 10:45

    $stmt = $pdo->prepare("SELECT id FROM exams WHERE title = 'Ujian Seleksi Masuk PPDB - Sesi II'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<br><b style='color:orange'>Ujian Sesi II sudah ada, dilewati.</b><br>";
    } else {
        $token2 = 'PPDB-SESI2-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $stmt = $pdo->prepare("
            INSERT INTO exams (title, category, subject_id, duration, start_time, end_time, token, randomize_questions, exam_type, status, created_by, created_at)
            VALUES ('Ujian Seleksi Masuk PPDB - Sesi II', 'ppdb', NULL, 155, ?, ?, ?, 0, 'multi', 'draft', 1, NOW())
        ");
        $stmt->execute([$sesi2Start, $sesi2End, $token2]);
        $sesi2Id = $pdo->lastInsertId();
        echo "<br>Ujian <b>Sesi II</b> dibuat (id: $sesi2Id)<br>";

        // Tambah exam_subjects untuk Sesi II
        $sesi2Subjects = [
            ['IPS PPDB',    60, 1],
            ['Bahasa PPDB', 50, 2],
            ['TPS PPDB',    45, 3],
        ];
        foreach ($sesi2Subjects as [$name, $duration, $order]) {
            $sid = $subjectIds[$name];
            $stmt = $pdo->prepare("INSERT INTO exam_subjects (exam_id, subject_id, order_num) VALUES (?, ?, ?)");
            $stmt->execute([$sesi2Id, $sid, $order]);
            echo "&nbsp;&nbsp;→ Subject <b>$name</b> ($duration menit) ditambahkan ke Sesi II<br>";
        }
    }

    $pdo->commit();

    echo "<br><div style='color:green; font-weight:bold'>✅ Setup selesai!</div>";
    echo "<br>Langkah selanjutnya:";
    echo "<ol>";
    echo "<li>Buka <a href='/exam?tab=ppdb'>Daftar Ujian → tab PPDB</a></li>";
    echo "<li>Klik <b>Kelola Soal</b> pada masing-masing sesi untuk menambahkan soal</li>";
    echo "<li>Setelah soal siap, klik <b>Publish</b></li>";
    echo "</ol>";

} catch (PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo "<div style='color:red'><b>Gagal:</b> " . $e->getMessage() . "</div>";
}
