<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Cari atau buat subject TPS
$stmt = $pdo->query("SELECT id FROM subjects WHERE name = 'TPS' LIMIT 1");
$subject = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$subject) {
    $pdo->exec("INSERT INTO subjects (name, description, created_at) VALUES ('TPS', 'Tes Potensi Skolastik', NOW())");
    $subjectId = $pdo->lastInsertId();
} else {
    $subjectId = $subject['id'];
}

// Cari admin user
$stmt = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$adminId = $admin['id'];

// Teks untuk soal 1-5
$teks1 = "Teks 1 (Kebijakan Pendidikan & Birokrasi)\n\nKebijakan pendidikan yang efektif tidak hanya bergantung pada desain kurikulum, tetapi pada bagaimana kebijakan tersebut diimplementasikan dalam struktur birokrasi sekolah. Ketika kontrol administratif terlalu dominan, inovasi cenderung stagnan. Namun, ketika kontrol dilemahkan tanpa mekanisme akuntabilitas, kualitas pembelajaran menjadi tidak terstandar. Oleh karena itu, keseimbangan antara kontrol dan otonomi menjadi faktor kunci keberhasilan institusi pendidikan.";

// Teks untuk soal 6-10
$teks2 = "Teks 2 (Literasi Digital & Masyarakat)\n\nKemampuan literasi digital tidak hanya berkaitan dengan akses teknologi, tetapi juga kemampuan memilah informasi. Di era banjir informasi, individu yang tidak memiliki kemampuan evaluatif cenderung terjebak dalam bias konfirmasi. Hal ini berdampak pada pengambilan keputusan yang tidak rasional, baik dalam konteks sosial maupun profesional.";

// Soal pilihan berganda (35 soal, poin 1)
$mcQuestions = [
    // Soal 1-5 (Teks 1)
    [1, "$teks1\n\n1. Inferensi utama teks adalah...", 'Kurikulum adalah faktor utama', 'Birokrasi harus dihapus', 'Keseimbangan kontrol dan otonomi menentukan kualitas', 'Guru harus bebas sepenuhnya', 'C'],
    [2, "$teks1\n\n2. Jika kontrol diperkuat ekstrem, dampaknya...", 'Inovasi meningkat', 'Standar naik drastis', 'Inovasi terhambat', 'Kualitas tidak berubah', 'C'],
    [3, "$teks1\n\n3. Jika otonomi tanpa akuntabilitas, implikasinya...", 'Inovasi terkendali', 'Standar tidak konsisten', 'Birokrasi kuat', 'Kurikulum berubah', 'B'],
    [4, "$teks1\n\n4. Paradoks utama teks adalah...", 'Kurikulum vs guru', 'Kontrol vs inovasi', 'Administrasi vs siswa', 'Guru vs birokrasi', 'B'],
    [5, "$teks1\n\n5. Kesimpulan paling logis...", 'Kontrol lebih penting dari inovasi', 'Inovasi lebih penting dari aturan', 'Keseimbangan adalah solusi sistemik', 'Kurikulum tidak penting', 'C'],
    // Soal 6-10 (Teks 2)
    [6, "$teks2\n\n6. Ide pokok teks adalah...", 'Teknologi penting', 'Literasi digital adalah kemampuan evaluatif', 'Informasi terlalu banyak', 'Keputusan selalu salah', 'B'],
    [7, "$teks2\n\n7. Bias konfirmasi menyebabkan...", 'Objektivitas meningkat', 'Informasi diverifikasi', 'Individu memilih informasi sesuai keyakinan', 'Data lebih valid', 'C'],
    [8, "$teks2\n\n8. Dampak sosial dari bias ini adalah...", 'Konsensus meningkat', 'Polarisasi meningkat', 'Informasi stabil', 'Literasi tinggi', 'B'],
    [9, "$teks2\n\n9. Makna \"evaluatif\" adalah...", 'Menghafal informasi', 'Menilai kualitas informasi', 'Menyebarkan informasi', 'Mengumpulkan data', 'B'],
    [10, "$teks2\n\n10. Jika literasi rendah, maka...", 'Informasi akurat', 'Keputusan rasional', 'Rentan disinformasi', 'Data meningkat', 'C'],
    // Soal 11-20 (Bahasa)
    [11, "11. Kalimat paling efektif adalah...", 'Para siswa-siswa diharapkan untuk dapat mengumpulkan tugas', 'Siswa diharapkan mengumpulkan tugas', 'Diharapkan agar siswa mengumpulkan tugas segera', 'Siswa agar supaya mengumpulkan tugas', 'B'],
    [12, "12. Kalimat ambigu adalah...", 'Guru baru itu ramah', 'Guru baru itu mengajar siswa baru', 'Guru baru itu mengajar', 'Guru itu ramah', 'B'],
    [13, "13. Makna implisit kalimat \"Ia naik daun\" adalah...", 'Jatuh', 'Terkenal', 'Sedih', 'Pergi', 'B'],
    [14, "14. Hubungan makna: Kritis : Analisis = Kreatif : ...", 'Imajinasi', 'Meniru', 'Menghafal', 'Menyalin', 'A'],
    [15, "15. Kalimat logis adalah...", 'Ia sangat pintar sekali', 'Ia paling pintar sekali', 'Ia sangat pintar', 'Ia paling sangat pintar', 'C'],
    [16, "16. Makna kontekstual \"akar masalah\" adalah...", 'Tanaman', 'Penyebab utama', 'Permukaan', 'Hasil', 'B'],
    [17, "17. Kalimat tidak efektif adalah...", 'Ia pergi ke sekolah', 'Ia pergi ke sekolah setiap hari', 'Ia pergi ke sekolah setiap hari sekali', 'Ia rajin ke sekolah', 'C'],
    [18, "18. Sinonim \"esensial\" adalah...", 'Tambahan', 'Penting', 'Kecil', 'Tidak perlu', 'B'],
    [19, "19. Antonim \"objektif\" adalah...", 'Netral', 'Subjektif', 'Rasional', 'Faktual', 'B'],
    [20, "20. Makna implisit: \"Ia bermain api\" adalah...", 'Bermain sungguhan', 'Mengambil risiko besar', 'Aman', 'Tidak berbahaya', 'B'],
    // Soal 21-35 (Logika & Penalaran)
    [21, "21. Semua guru disiplin. Sebagian guru kreatif. Maka...", 'Semua kreatif disiplin', 'Sebagian kreatif disiplin', 'Tidak ada hubungan', 'Semua disiplin kreatif', 'B'],
    [22, "22. Jika hujan maka jalan basah. Jalan tidak basah → ...", 'Hujan', 'Tidak hujan', 'Mendung', 'Tidak pasti', 'B'],
    [23, "23. Analogi: KURIKULUM : PEMBELAJARAN = PEDOMAN : ...", 'Arah', 'Pelaksanaan', 'Aturan', 'Tujuan', 'B'],
    [24, "24. Analogi sebab-akibat: LATIHAN : KEAHLIAN = ... : ...", 'Belajar : Lupa', 'Praktik : Mahir', 'Tidur : Lelah', 'Main : Bosan', 'B'],
    [25, "25. Urutan logis: Observasi – Hipotesis – ... – Kesimpulan", 'Data', 'Eksperimen', 'Teori', 'Fakta', 'B'],
    [26, "26. Premis: Semua siswa rajin lulus. Andi tidak lulus → ...", 'Andi rajin', 'Andi tidak rajin', 'Andi mungkin rajin', 'Tidak bisa disimpulkan', 'B'],
    [27, "27. Analogi relasi: PISAU : MEMOTONG = ... : ...", 'Buku : Membaca', 'Pena : Menulis', 'Air : Minum', 'Meja : Duduk', 'B'],
    [28, "28. Kalimat efektif adalah...", 'Demi untuk meningkatkan mutu', 'Untuk meningkatkan mutu', 'Agar supaya meningkat', 'Untuk agar mutu', 'B'],
    [29, "29. Hubungan logis: Jika A benar maka B benar. B salah → ...", 'A benar', 'A salah', 'Tidak pasti', 'B benar', 'B'],
    [30, "30. Kata baku yang benar adalah...", 'Resiko', 'Risiko', null, null, 'B'],
    [31, "31. Analogi struktur: NEGARA : KONSTITUSI = SEKOLAH : ...", 'Guru', 'Tata tertib', 'Siswa', 'Kepala sekolah', 'B'],
    [32, "32. Kalimat ambigu: ...", 'Ia melihat orang dengan teropong', 'Ia membaca buku', 'Ia menulis surat', 'Ia makan nasi', 'A'],
    [33, "33. Hubungan konsep: TEORI : PRAKTIK = RENCANA : ...", 'Ide', 'Implementasi', 'Konsep', 'Analisis', 'B'],
    [34, "34. Kalimat paling tepat adalah...", 'Ia sangat unik sekali', 'Ia unik', 'Ia paling unik sekali', 'Ia sangat paling unik', 'B'],
    [35, "35. Hubungan logika: Semua A adalah B. Semua B adalah C → ...", 'Semua A adalah C', 'Semua C adalah A', 'Sebagian A bukan C', 'Tidak ada hubungan', 'A'],
];

// Soal essay (5 soal, poin 2)
$essayQuestions = [
    [36, "36. Perbaiki kalimat tidak efektif berikut:\n\"Para siswa-siswa diharapkan agar supaya segera mengumpulkan tugas.\"", 'Siswa diharapkan segera mengumpulkan tugas'],
    [37, "37. Jelaskan makna implisit \"bermain dua kaki\"", 'Tidak konsisten / berpihak ganda'],
    [38, "38. Buat satu kalimat efektif dari kata: analisis – data – keputusan", 'Keputusan diambil berdasarkan analisis data'],
    [39, "39. Sebutkan antonim dari \"rasional\"", 'Irasional'],
    [40, "40. Tuliskan satu contoh kalimat retoris", 'Apakah kita akan diam melihat pendidikan menurun?'],
];

$pdo->beginTransaction();

$insertQ = $pdo->prepare("INSERT INTO questions (subject_id, type, question_text, option_a, option_b, option_c, option_d, correct_answer, points, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");

$questionIds = [];

// Insert MC soal
foreach ($mcQuestions as $q) {
    $insertQ->execute([$subjectId, 'multiple_choice', $q[1], $q[2], $q[3], $q[4] ?? null, $q[5] ?? null, $q[6], 1, $adminId]);
    $questionIds[] = $pdo->lastInsertId();
}

// Insert essay soal
foreach ($essayQuestions as $q) {
    $insertQ->execute([$subjectId, 'essay', $q[1], null, null, null, null, $q[2], 2, $adminId]);
    $questionIds[] = $pdo->lastInsertId();
}

// Buat ujian
$token = 'TPS-' . strtoupper(substr(md5(uniqid()), 0, 8));
$pdo->prepare("INSERT INTO exams (title, category, subject_id, duration, start_time, end_time, token, randomize_questions, exam_type, status, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())")
    ->execute(['SELEKSI GURU TES POTENSI SKOLASTIK (TPS)', 'reguler', $subjectId, 30, '2026-04-10 08:00:00', '2026-04-10 10:00:00', $token, 0, 'single', 'draft', $adminId]);
$examId = $pdo->lastInsertId();

// Assign soal ke ujian
$insertEQ = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, order_num) VALUES (?,?,?)");
foreach ($questionIds as $i => $qId) {
    $insertEQ->execute([$examId, $qId, $i + 1]);
}

// Assign ke kelas CALON-GURU
$students = $pdo->query("SELECT id FROM users WHERE kelas='CALON-GURU' AND role='siswa'")->fetchAll(PDO::FETCH_COLUMN);
$insP = $pdo->prepare("INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?,?)");
foreach ($students as $sid) { $insP->execute([$examId, $sid]); }

$pdo->commit();

echo "<div style='font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;background:#d1fae5;border-radius:8px'>";
echo "<h3 style='color:#065f46'>✅ Ujian TPS berhasil dibuat!</h3>";
echo "<p><b>Judul:</b> SELEKSI GURU TES POTENSI SKOLASTIK (TPS)</p>";
echo "<p><b>Durasi:</b> 30 menit</p>";
echo "<p><b>Total Soal:</b> 40 soal (35 PG + 5 Essay)</p>";
echo "<p><b>Total Poin:</b> 35×1 + 5×2 = <b>45 poin</b></p>";
echo "<p><b>Peserta:</b> ".count($students)." calon guru (CALON-GURU)</p>";
echo "<p><b>Status:</b> Draft — silakan set tanggal ujian dan publish di menu Ujian (ID: $examId)</p>";
echo "<p><a href='exam/edit/$examId' style='color:#6366f1'>→ Edit & Set Tanggal Ujian</a></p>";
echo "</div>";
