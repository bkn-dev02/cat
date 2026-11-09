<?php

namespace App\Controllers;

class DashboardController
{
    /**
     * ============================================================
     * HELPER SCORING TERPUSAT
     * ============================================================
     * Kenapa dibutuhkan:
     * - Soal reguler: benar/salah biner -> dapat q.points penuh atau 0
     * - Soal TKP (dan tipe skala lain di masa depan): setiap pilihan
     *   (A-E) punya skor sendiri, disimpan di kolom questions.option_scores
     *   sebagai JSON, contoh: {"a":2,"b":4,"c":1,"d":5,"e":3}
     *
     * CATATAN PENTING: Fungsi di bawah ini sudah otomatis fallback ke 
     * logika biner selama option_scores kosong. Begitu option_scores 
     * diisi, nilai akan otomatis terhitung dengan benar.
     */

    /** Fragment SQL: hitung skor satu jawaban (dipakai di dalam SUM(...)) */
    private static function scoreExprSql(string $answerAlias, string $questionAlias): string
    {
        // Fallback case-insensitive: coba key lowercase dulu, jika null coba uppercase
        $lowerKey = "CONCAT('$.', LOWER(TRIM({$answerAlias}.answer)))";
        $upperKey = "CONCAT('$.', UPPER(TRIM({$answerAlias}.answer)))";
        
        return "
            CASE
                WHEN {$questionAlias}.option_scores IS NOT NULL AND {$questionAlias}.option_scores != ''
                    THEN COALESCE(
                        CAST(JSON_UNQUOTE(JSON_EXTRACT({$questionAlias}.option_scores, {$lowerKey})) AS DECIMAL(10,2)),
                        CAST(JSON_UNQUOTE(JSON_EXTRACT({$questionAlias}.option_scores, {$upperKey})) AS DECIMAL(10,2)),
                        0
                    )
                WHEN LOWER(TRIM({$answerAlias}.answer)) = LOWER(TRIM({$questionAlias}.correct_answer))
                    THEN {$questionAlias}.points
                ELSE 0
            END
        ";
    }

    /** Fragment SQL: apakah jawaban "benar" (untuk badge ✓/✗ dan hitung jumlah_benar).
     *  Untuk soal skala (TKP dkk), dianggap "benar" kalau dapat skor maksimal soal itu. */
    private static function isCorrectExprSql(string $answerAlias, string $questionAlias): string
    {
        $lowerKey = "CONCAT('$.', LOWER(TRIM({$answerAlias}.answer)))";
        $upperKey = "CONCAT('$.', UPPER(TRIM({$answerAlias}.answer)))";

        return "
            CASE
                WHEN {$questionAlias}.option_scores IS NOT NULL AND {$questionAlias}.option_scores != ''
                    THEN CASE WHEN COALESCE(
                            CAST(JSON_UNQUOTE(JSON_EXTRACT({$questionAlias}.option_scores, {$lowerKey})) AS DECIMAL(10,2)),
                            CAST(JSON_UNQUOTE(JSON_EXTRACT({$questionAlias}.option_scores, {$upperKey})) AS DECIMAL(10,2)),
                            0
                        ) >= {$questionAlias}.points THEN 1 ELSE 0 END
                WHEN LOWER(TRIM({$answerAlias}.answer)) = LOWER(TRIM({$questionAlias}.correct_answer))
                    THEN 1
                ELSE 0
            END
        ";
    }

    /** Versi PHP (untuk kasus di mana kita sudah fetch data ke PHP, bukan hitung di SQL).
     *  Return: [skor_didapat, is_correct] */
    private static function calcAnswerScore($optionScores, ?string $studentAnswer, float $points, ?string $correctAnswer): array
    {
        // Handle jika option_scores sudah berupa array (auto-decoded oleh PDO) atau string JSON
        $scores = is_array($optionScores) ? $optionScores : json_decode((string)$optionScores, true);
        
        if (is_array($scores)) {
            $key = strtolower(trim((string)$studentAnswer));
            
            // Antisipasi jika key di JSON ternyata uppercase (misal: {"A": 5, "B": 4})
            if (!array_key_exists($key, $scores)) {
                $keyUpper = strtoupper(trim((string)$studentAnswer));
                if (array_key_exists($keyUpper, $scores)) {
                    $key = $keyUpper;
                }
            }

            if (array_key_exists($key, $scores)) {
                $score = (float)$scores[$key];
                // Dianggap "benar" jika skor yang didapat >= skor maksimal (points)
                $isCorrect = $score >= $points;
                return [$score, $isCorrect];
            }
        }
        
        // fallback: soal reguler benar/salah
        $isCorrect = strtolower(trim((string)$studentAnswer)) === strtolower(trim((string)$correctAnswer));
        return [$isCorrect ? $points : 0, $isCorrect];
    }

    public function index()
    {
        requireAuth();
        $user = auth();

        try {
            // Get subject info for guru
            $subjectName = null;
            if ($user['role'] === 'guru' && !empty($user['subject_id'])) {
                $stmt = db()->prepare("SELECT name FROM subjects WHERE id = ?");
                $stmt->execute([$user['subject_id']]);
                $subjectName = $stmt->fetchColumn();
            }

            // Get exams data
            if ($user['role'] === 'siswa') {
                $stmt = db()->prepare("
                    SELECT e.*, s.name as subject_name,
                        CASE
                            WHEN NOW() < e.start_time THEN 'upcoming'
                            WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'active'
                            ELSE 'ended'
                        END as exam_status,
                        es.id as session_id,
                        es.score as session_score,
                        es.status as session_status,
                        (es.id IS NOT NULL) as has_taken,
                        (NOT EXISTS(SELECT 1 FROM exam_participants WHERE exam_id = e.id) OR 
                         EXISTS(SELECT 1 FROM exam_participants WHERE exam_id = e.id AND user_id = ?)) as can_access
                    FROM exams e
                    LEFT JOIN subjects s ON e.subject_id = s.id
                    LEFT JOIN exam_sessions es 
                        ON es.exam_id = e.id 
                        AND es.user_id = ? 
                        AND es.status IN ('submitted','graded','completed')
                        AND es.submitted_at IS NOT NULL
                    WHERE e.status = 'published' AND e.end_time >= NOW()
                    ORDER BY e.start_time ASC
                    LIMIT 5
                ");
                $stmt->execute([$user['id'], $user['id']]);
            } else {
                $stmt = db()->prepare("
                    SELECT e.*, s.name as subject_name,
                        (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count,
                        (SELECT COUNT(*) FROM exam_sessions WHERE exam_id = e.id) as session_count,
                        (SELECT COUNT(*) FROM exam_sessions WHERE exam_id = e.id AND status = 'in_progress') as active_sessions
                    FROM exams e
                    LEFT JOIN subjects s ON e.subject_id = s.id
                    WHERE e.created_by = ?
                    ORDER BY e.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$user['id']]);
            }
            $exams = $stmt->fetchAll();

            $availableExamCount = 0;
            $completedExamCount = 0;
            if (in_array($user['role'], ['siswa', 'calon-siswa'], true)) {
                $stmt = db()->prepare("
                    SELECT
                        (
                            SELECT COUNT(DISTINCT e.id)
                            FROM exams e
                            LEFT JOIN exam_participants ep
                                ON ep.exam_id = e.id AND ep.user_id = ?
                            WHERE e.status = 'published'
                              AND e.end_time >= NOW()
                              AND (
                                  NOT EXISTS (
                                      SELECT 1 FROM exam_participants
                                      WHERE exam_id = e.id
                                  )
                                  OR ep.user_id IS NOT NULL
                              )
                        ) AS available_exam_count,
                        (
                            SELECT COUNT(*)
                            FROM exam_sessions
                            WHERE user_id = ? AND status = 'graded'
                        ) AS completed_exam_count
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $counts = $stmt->fetch();
                $availableExamCount = (int)$counts['available_exam_count'];
                $completedExamCount = (int)$counts['completed_exam_count'];
            }
            
            // Hitung total ujian & bank soal berdasarkan input guru yang login
            $totalUjian = 0;
            $totalBankSoal = 0;
            
            if ($user['role'] === 'guru') {
                $stmt = db()->prepare("SELECT COUNT(*) FROM exams WHERE created_by = ?");
                $stmt->execute([$user['id']]);
                $totalUjian = $stmt->fetchColumn();
            
                $stmt = db()->prepare("SELECT COUNT(*) FROM questions WHERE created_by = ?");
                $stmt->execute([$user['id']]);
                $totalBankSoal = $stmt->fetchColumn();
            } elseif ($user['role'] !== 'siswa') {
                // admin total keseluruhan
                $totalUjian = db()->query("SELECT COUNT(*) FROM exams")->fetchColumn();
                $totalBankSoal = db()->query("SELECT COUNT(*) FROM questions")->fetchColumn();
            }

            ob_start();
            include __DIR__ . '/../../views/dashboard.php';
            $output = ob_get_clean();
            echo $output;

        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            error_log("Dashboard FATAL ERROR: " . $e->getMessage() . " di " . $e->getFile() . ":" . $e->getLine());
            error_log($e->getTraceAsString());

            http_response_code(500);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
            <title>Dashboard - CAT Enterprise</title>
            <style>
                body{font-family:sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
                .box{background:white;padding:2.5rem;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center;max-width:420px;}
                .box i{font-size:3rem;color:#f59e0b;}
                .box h2{color:#1e293b;margin:1rem 0 .5rem;}
                .box p{color:#64748b;font-size:.9rem;margin-bottom:1.5rem;}
                .box a{background:#6366f1;color:white;text-decoration:none;padding:.75rem 1.5rem;border-radius:8px;font-weight:600;}
            </style></head><body>
            <div class="box">
                <i>⚠️</i>
                <h2>Dashboard sedang bermasalah</h2>
                <p>Terjadi kesalahan saat memuat dashboard. Tim kami sudah diberitahu otomatis. Silakan coba lagi.</p>
                <a href="' . htmlspecialchars(config('app_url') ?? '/') . '/exam">Lihat Daftar Ujian</a>
            </div>
            </body></html>';
        }
    }

    /**
     * ============================================================
     * EXPORT NILAI (Excel / CSV)
     * ============================================================
     * Populasi siswa & sumber nilai di-sesuaikan supaya SAMA PERSIS
     * dengan laporan PDF (ReportController::laporanNilai):
     *
     * 1. Populasi: SEMUA siswa (role='siswa') yang jadi peserta ujian
     *    (exam_participants) ATAU sudah punya sesi — termasuk yang
     *    BELUM mengerjakan. Sesi diambil yang TERBARU, apa pun
     *    statusnya (tidak dibatasi ke submitted/graded/dst saja).
     *
     * 2. Nilai: pakai es.score (nilai tersimpan) apa adanya, TIDAK
     *    ikut difilter per mapel — sama seperti PDF. Nilai hanya
     *    dihitung ulang (fallback) kalau es.score NULL padahal siswa
     *    sudah menjawab.
     *
     * catch(\Throwable) dipakai (bukan \Exception saja) supaya semua
     * jenis fatal error ikut tertangkap dan tidak menghasilkan
     * halaman putih kosong (500) — pesannya akan tampil di dashboard.
     * ============================================================
     */
    public function exportNilai()
    {
        if (!isLoggedIn()) { redirect('auth/login'); }
        $user = auth();
        if ($user['role'] === 'siswa') {
            $_SESSION['error'] = 'Tidak memiliki akses';
            redirect('dashboard');
        }

        try {
            $examId     = $_GET['exam_id'] ?? null;
            $namaKelas  = $_GET['namaKelas'] ?? null;
            $subjectId  = $_GET['subject_id'] ?? null;
            $format     = $_GET['format'] ?? 'csv'; // csv | excel

            // Sama seperti laporan PDF: wajib pilih ujian dulu
            if (!$examId) {
                $_SESSION['error'] = 'Pilih ujian terlebih dahulu';
                redirect('dashboard');
            }

            // Info ujian
            $examStmt = db()->prepare("SELECT title FROM exams WHERE id = ?");
            $examStmt->execute([$examId]);
            $examTitle = $examStmt->fetchColumn() ?: ('Ujian #' . $examId);

            // Nama subject jika ada filter subject_id
            $subjectName = null;
            if ($subjectId) {
                $subjStmt = db()->prepare("SELECT name FROM subjects WHERE id = ?");
                $subjStmt->execute([$subjectId]);
                $subjectName = $subjStmt->fetchColumn();
            }

            // ── AMBIL SEMUA MAPEL DALAM UJIAN (Untuk Breakdown Excel) ──
            $stmtMapel = db()->prepare("
                SELECT DISTINCT s.id, s.name
                FROM exam_questions eq
                JOIN questions q ON q.id = eq.question_id
                JOIN subjects s ON s.id = q.subject_id
                WHERE eq.exam_id = ?
                ORDER BY s.name ASC
            ");
            $stmtMapel->execute([$examId]);
            $allSubjectsInExam = $stmtMapel->fetchAll();

            // Tentukan Mode: Breakdown per Mapel atau Sederhana?
            $isBreakdownMode = !$subjectId && count($allSubjectsInExam) > 1;

            // ── Daftar soal ujian (difilter mapel jika ada) — SAMA seperti PDF ──
            $questionQuery = "
                SELECT q.id, q.type, q.correct_answer, q.option_scores, q.points, q.subject_id
                FROM exam_questions eq
                JOIN questions q ON q.id = eq.question_id
                WHERE eq.exam_id = ?
            ";
            $qParams = [$examId];
            if ($subjectId) {
                $questionQuery .= " AND q.subject_id = ?";
                $qParams[] = $subjectId;
            }
            $qStmt = db()->prepare($questionQuery);
            $qStmt->execute($qParams);
            $questions = $qStmt->fetchAll();

            // total soal non-essay/tkp (untuk kolom Total Soal mode sederhana)
            $totalSoalObjektif = count(array_filter(
                $questions,
                function ($q) {
                    $type = $q['type'] ?? 'multiple_choice';
                    return !in_array($type, ['essay', 'tkp'], true);
                }
            ));

            // ── Populasi siswa — SAMA PERSIS dengan ReportController::laporanNilai() ──
            $query = "
                SELECT
                    u.id AS user_id, u.username, u.full_name, u.kelas,
                    es.id AS session_id, es.status,
                    es.started_at AS waktu_mulai, es.submitted_at AS waktu_selesai,
                    es.score AS nilai_tersimpan
                FROM users u
                LEFT JOIN exam_sessions es ON es.id = (
                    SELECT id FROM exam_sessions
                    WHERE user_id = u.id AND exam_id = ?
                    ORDER BY
                        CASE WHEN submitted_at IS NOT NULL THEN 0 ELSE 1 END,
                        submitted_at DESC,
                        id DESC
                    LIMIT 1
                )
                WHERE u.role = 'siswa'
            ";
            $params = [$examId];

            if ($namaKelas) {
                $query .= " AND u.kelas = ?";
                $params[] = $namaKelas;
            } else {
                $tblCheck = db()->query("SHOW TABLES LIKE 'exam_participants'")->rowCount();
                if ($tblCheck > 0) {
                    $query .= " AND (
                        EXISTS (SELECT 1 FROM exam_participants ep WHERE ep.exam_id = ? AND ep.user_id = u.id)
                        OR es.id IS NOT NULL
                    )";
                    $params[] = $examId;
                } else {
                    $query .= " AND es.id IS NOT NULL";
                }
            }
            $query .= " ORDER BY u.kelas, u.full_name";

            $stmt = db()->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // Handle jika data kosong (mencegah download dashboard.html)
            if (empty($rows)) {
                if (ob_get_level()) ob_end_clean();

                if ($format === 'excel') {
                    $filename = 'Laporan_Kosong.xls';
                    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    echo '<html><head><meta charset="UTF-8"></head><body>';
                    echo '<h2 style="color:red; font-family: Arial;">⚠️ Tidak ada data siswa untuk diexport</h2>';
                    echo '<p>Pastikan ada siswa yang menjadi peserta ujian ini, atau cek kembali filter Kelas.</p>';
                    echo '</body></html>';
                    exit;
                } else {
                    $filename = 'Laporan_Kosong.csv';
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    echo "PESAN\n";
                    echo "Tidak ada data siswa untuk diexport.\n";
                    exit;
                }
            }

            // ── Susun data per siswa (nilai, benar/salah, skor TKP per mapel) ──
            // Logikanya mengikuti ReportController::laporanNilai() supaya angka SAMA dengan PDF.
            $results = [];
            $answersBySession = answersForSessions(array_column($rows, 'session_id'));
            foreach ($rows as $r) {
                $sessionId  = $r['session_id'];
                $sudahUjian = ($sessionId !== null); // sama seperti PDF: punya sesi = "sudah ujian"

                $answers = $answersBySession[$sessionId] ?? [];

                // Nilai: prioritaskan es.score (nilai tersimpan), TIDAK dipengaruhi filter mapel,
                // sama seperti PDF. Hanya hitung ulang kalau nilai tersimpan NULL padahal ada jawaban.
                $nilai = 0;
                if ($sudahUjian) {
                    if ($r['nilai_tersimpan'] !== null) {
                        $nilai = (float)$r['nilai_tersimpan'];
                    } elseif (count($answers) > 0) {
                        $recovery = 0;
                        foreach ($questions as $q) {
                            $type = $q['type'] ?? 'multiple_choice';
                            if ($type === 'essay') continue;
                            $ans = $answers[$q['id']] ?? null;
                            if ($ans === null || trim((string)$ans) === '') continue;
                            [$score, ] = self::calcAnswerScore(
                                $q['option_scores'], (string)$ans, (float)$q['points'], $q['correct_answer']
                            );
                            $recovery += $score;
                        }
                        $nilai = $recovery;
                    }
                }

                // Breakdown Benar/Salah & skor TKP per mapel
                $benarPerMapel   = [];
                $skorTkpPerMapel = [];
                foreach ($allSubjectsInExam as $subj) {
                    $benarPerMapel[$subj['id']]   = 0;
                    $skorTkpPerMapel[$subj['id']] = 0;
                }

                $jumlahBenar = 0;
                if ($sudahUjian) {
                    foreach ($questions as $q) {
                        $type = $q['type'] ?? 'multiple_choice';
                        if ($type === 'essay') continue;

                        $sid = $q['subject_id'];
                        if ($sid === null) continue;

                        $ans = $answers[$q['id']] ?? null;
                        if ($ans === null || trim((string)$ans) === '') continue;

                        [$score, $isCorrect] = self::calcAnswerScore(
                            $q['option_scores'], (string)$ans, (float)$q['points'], $q['correct_answer']
                        );

                        $isTkpQuestion = !empty($q['option_scores']) || $type === 'tkp';

                        if ($isTkpQuestion) {
                            $skorTkpPerMapel[$sid] = ($skorTkpPerMapel[$sid] ?? 0) + $score;
                            continue;
                        }

                        if ($isCorrect) {
                            $jumlahBenar++;
                            $benarPerMapel[$sid] = ($benarPerMapel[$sid] ?? 0) + 1;
                        }
                    }
                }

                $results[] = [
                    'username'           => $r['username'],
                    'full_name'          => $r['full_name'],
                    'kelas'              => $r['kelas'],
                    'ujian'              => $examTitle,
                    'mata_pelajaran'     => $subjectName ?: ($isBreakdownMode ? 'Semua Mapel (Breakdown)' : 'Semua Mata Pelajaran'),
                    'nilai'              => $nilai,
                    'sudah_ujian'        => $sudahUjian,
                    'status'             => $sudahUjian ? ($r['status'] ?? 'submitted') : 'belum_ujian',
                    'waktu_mulai'        => $r['waktu_mulai'] ?? '',
                    'waktu_selesai'      => $r['waktu_selesai'] ?? '',
                    'jumlah_benar'       => $jumlahBenar,
                    'total_soal'         => $totalSoalObjektif,
                    'benar_per_mapel'    => $benarPerMapel,
                    'skor_tkp_per_mapel' => $skorTkpPerMapel,
                ];
            }

            if (ob_get_level()) ob_end_clean();

            $suffix = ($namaKelas ? 'namaKelas_' . str_replace(' ', '_', $namaKelas) . '_' : '')
                    . ($subjectId ? 'subject_' . $subjectId . '_' : '')
                    . date('Y-m-d_His');

            // ── Hitung statistik ringkasan — SAMA seperti PDF (hanya dari yang SUDAH ujian) ──
            $totalSiswa      = count($results);
            $sudahUjianRows  = array_filter($results, function ($r) { return $r['sudah_ujian']; });
            $sudahCount      = count($sudahUjianRows);
            $belumUjianCount = $totalSiswa - $sudahCount;

            $totalNilai = 0;
            $totalLulus = 0;
            $highest = 0;
            $lowest  = PHP_FLOAT_MAX;

            foreach ($sudahUjianRows as $r) {
                $nilai = (float)$r['nilai'];
                $totalNilai += $nilai;
                if ($nilai >= 75) $totalLulus++;
                if ($nilai > $highest) $highest = $nilai;
                if ($nilai < $lowest)  $lowest = $nilai;
            }
            $totalTidakLulus = $sudahCount - $totalLulus;
            $rataRata        = $sudahCount > 0 ? ($totalNilai / $sudahCount) : 0;
            if ($sudahCount === 0) $lowest = 0;

            $dynamicHeaders = [];
            foreach ($allSubjectsInExam as $subj) {
                $isTkpSubject = (stripos($subj['name'], 'tkp') !== false);
                $dynamicHeaders[] = ['id' => (int)$subj['id'], 'name' => $subj['name'], 'is_tkp' => $isTkpSubject];
            }

            if ($format === 'excel') {
                $filename = 'nilai_' . $suffix . '.xls';
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: no-cache');
                header('Pragma: public');

                echo chr(0xEF).chr(0xBB).chr(0xBF); // BOM UTF-8

                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head>';
                echo '<meta charset="UTF-8">';
                echo '<!--[if gte mso 9]><xml>';
                echo '<x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
                echo '<x:Name>Laporan Nilai</x:Name>';
                echo '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
                echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>';
                echo '</xml><![endif]-->';
                echo '<style>';
                echo 'body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #1e293b; }';
                echo '.report-title { text-align: center; font-size: 16pt; font-weight: bold; margin: 0 0 4px 0; color: #1e293b; }';
                echo '.report-subtitle { text-align: center; font-size: 12pt; margin: 0 0 12px 0; color: #475569; }';
                echo '.info-filter { margin: 0 0 12px 0; font-size: 10pt; }';
                echo '.info-filter td { padding: 2px 8px; vertical-align: top; }';
                echo '.info-filter .label { font-weight: bold; width: 120px; }';
                echo 'table.main-table { border-collapse: collapse; width: 100%; margin-top: 8px; }';
                echo 'table.main-table th { background-color: #1e293b; color: #ffffff; font-weight: bold; padding: 8px 6px; border: 1px solid #000000; text-align: center; vertical-align: middle; font-size: 10pt; }';
                echo 'table.main-table th.mapel-header { background-color: #475569; font-size: 10pt; }';
                echo 'table.main-table th.sub-header { background-color: #1e293b; font-size: 9pt; }';
                echo 'table.main-table td { padding: 6px; border: 1px solid #000000; vertical-align: middle; font-size: 10pt; }';
                echo '.center { text-align: center; }';
                echo '.left { text-align: left; }';
                echo '.bold { font-weight: bold; }';
                echo '.lulus { background-color: #d1fae5; }';
                echo '.tidak-lulus { background-color: #fee2e2; }';
                echo '.belum-ujian { background-color: #fffbeb; color: #92400e; font-style: italic; }';
                echo '.summary-box { margin-top: 20px; border: 1px solid #1e293b; padding: 10px 14px; background-color: #f1f5f9; width: 480px; }';
                echo '.summary-box .title { font-weight: bold; font-size: 11pt; margin-bottom: 6px; color: #1e293b; border-bottom: 1px solid #94a3b8; padding-bottom: 4px; }';
                echo '.summary-box table { width: 100%; }';
                echo '.summary-box td { padding: 3px 6px; font-size: 10pt; border: 0; }';
                echo '.summary-box .sum-label { font-weight: bold; width: 200px; }';
                echo '.footer-note { margin-top: 24px; font-size: 9pt; color: #64748b; text-align: center; font-style: italic; }';
                echo '</style>';
                echo '</head>';
                echo '<body>';

                // ===== HEADER LAPORAN =====
                echo '<div class="report-title">LAPORAN HASIL UJIAN SISWA</div>';
                echo '<div class="report-subtitle">CAT Enterprise - Sistem Penilaian Terpusat</div>';

                echo '<table class="info-filter">';
                echo '<tr><td class="label">Tanggal Export</td><td>: ' . date('d-m-Y H:i:s') . '</td></tr>';
                echo '<tr><td class="label">Ujian</td><td>: ' . htmlspecialchars($examTitle) . '</td></tr>';
                echo '<tr><td class="label">Kelas</td><td>: ' . htmlspecialchars($namaKelas ?: 'Semua Kelas') . '</td></tr>';
                echo '<tr><td class="label">Mata Pelajaran</td><td>: ' . htmlspecialchars($subjectName ?: ($isBreakdownMode ? 'Semua Mapel (Breakdown)' : 'Semua Mata Pelajaran')) . '</td></tr>';
                echo '<tr><td class="label">Diekspor oleh</td><td>: ' . htmlspecialchars($user['full_name'] ?? $user['username']) . '</td></tr>';
                echo '</table>';

                // ===== TABEL UTAMA =====
                echo '<table class="main-table">';

                if ($isBreakdownMode) {
                    echo '<tr>';
                    echo '<th rowspan="2">No</th>';
                    echo '<th rowspan="2">Username</th>';
                    echo '<th rowspan="2">Nama Lengkap</th>';
                    echo '<th rowspan="2">Kelas</th>';
                    echo '<th rowspan="2">Ujian</th>';
                    echo '<th rowspan="2">Nilai</th>';

                    foreach ($dynamicHeaders as $dh) {
                        $colspan = $dh['is_tkp'] ? 1 : 2;
                        echo '<th class="mapel-header" colspan="' . $colspan . '">' . htmlspecialchars($dh['name']) . '</th>';
                    }

                    echo '<th rowspan="2">Waktu Mulai</th>';
                    echo '<th rowspan="2">Waktu Selesai</th>';
                    echo '<th rowspan="2">Keterangan</th>';
                    echo '</tr>';

                    echo '<tr>';
                    foreach ($dynamicHeaders as $dh) {
                        if ($dh['is_tkp']) {
                            echo '<th class="sub-header">Skor</th>';
                        } else {
                            echo '<th class="sub-header">Benar</th>';
                            echo '<th class="sub-header">Salah</th>';
                        }
                    }
                    echo '</tr>';
                } else {
                    echo '<tr>';
                    echo '<th>No</th>';
                    echo '<th>Username</th>';
                    echo '<th>Nama Lengkap</th>';
                    echo '<th>Kelas</th>';
                    echo '<th>Ujian</th>';
                    echo '<th>Mata Pelajaran</th>';
                    echo '<th>Nilai</th>';
                    echo '<th>Benar</th>';
                    echo '<th>Salah</th>';
                    echo '<th>Total Soal</th>';
                    echo '<th>Waktu Mulai</th>';
                    echo '<th>Waktu Selesai</th>';
                    echo '<th>Keterangan</th>';
                    echo '</tr>';
                }

                // ===== DATA ROWS =====
                $no = 1;
                foreach ($results as $r) {
                    $sudah = $r['sudah_ujian'];
                    $nilai = (float)$r['nilai'];
                    $lulus = $sudah && $nilai >= 75;

                    $rowClass = !$sudah ? 'belum-ujian' : ($lulus ? 'lulus' : 'tidak-lulus');

                    echo '<tr class="' . $rowClass . '">';
                    echo '<td class="center">' . $no++ . '</td>';
                    echo '<td class="left" x:str>' . htmlspecialchars((string)$r['username']) . '</td>';
                    echo '<td class="left">' . htmlspecialchars((string)$r['full_name']) . '</td>';
                    echo '<td class="center" x:str>' . htmlspecialchars((string)$r['kelas']) . '</td>';
                    echo '<td class="left">' . htmlspecialchars((string)$r['ujian']) . '</td>';

                    if (!$isBreakdownMode) {
                        echo '<td class="left">' . htmlspecialchars((string)$r['mata_pelajaran']) . '</td>';
                    }

                    echo '<td class="center bold">' . ($sudah ? number_format($nilai, 2, '.', '') : '-') . '</td>';

                    if ($isBreakdownMode) {
                        foreach ($dynamicHeaders as $dh) {
                            $sid = $dh['id'];
                            if ($dh['is_tkp']) {
                                $tkpScore = $sudah ? (int)($r['skor_tkp_per_mapel'][$sid] ?? 0) : 0;
                                echo '<td class="center bold">' . ($sudah ? $tkpScore : '-') . '</td>';
                            } else {
                                $benar = $sudah ? (int)($r['benar_per_mapel'][$sid] ?? 0) : 0;
                                $totalMapel = count(array_filter($questions, function ($q) use ($sid) {
                                    return ($q['subject_id'] == $sid) && !in_array($q['type'], ['essay', 'tkp'], true);
                                }));
                                $salah = max(0, $totalMapel - $benar);
                                echo '<td class="center">' . ($sudah ? $benar : '-') . '</td>';
                                echo '<td class="center">' . ($sudah ? $salah : '-') . '</td>';
                            }
                        }
                    } else {
                        $totalSoal   = (int)$r['total_soal'];
                        $jumlahBenar = (int)$r['jumlah_benar'];
                        $jumlahSalah = max(0, $totalSoal - $jumlahBenar);

                        echo '<td class="center">' . ($sudah ? $jumlahBenar : '-') . '</td>';
                        echo '<td class="center">' . ($sudah ? $jumlahSalah : '-') . '</td>';
                        echo '<td class="center">' . $totalSoal . '</td>';
                    }

                    echo '<td class="center" x:str>' . htmlspecialchars((string)$r['waktu_mulai']) . '</td>';
                    echo '<td class="center" x:str>' . htmlspecialchars((string)$r['waktu_selesai']) . '</td>';
                    echo '<td class="center" x:str>' . ($sudah ? ($lulus ? 'Lulus' : 'Tidak Lulus') : 'Belum Ujian') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                // ===== RINGKASAN / SUMMARY =====
                echo '<table class="summary-box">';
                echo '<tr><td colspan="2" class="title">RINGKASAN NILAI</td></tr>';
                echo '<tr><td class="sum-label">Total Siswa</td><td class="bold">: ' . $totalSiswa . ' siswa</td></tr>';
                echo '<tr><td class="sum-label">Sudah Ujian</td><td class="bold">: ' . $sudahCount . ' siswa</td></tr>';
                echo '<tr><td class="sum-label">Belum Ujian</td><td class="bold" style="color:#92400e;">: ' . $belumUjianCount . ' siswa</td></tr>';
                echo '<tr><td class="sum-label">Rata-rata Nilai</td><td class="bold">: ' . number_format($rataRata, 2, '.', '') . '</td></tr>';
                echo '<tr><td class="sum-label">Nilai Tertinggi</td><td class="bold">: ' . number_format($highest, 2, '.', '') . '</td></tr>';
                echo '<tr><td class="sum-label">Nilai Terendah</td><td class="bold">: ' . number_format($lowest, 2, '.', '') . '</td></tr>';
                echo '<tr><td class="sum-label">Lulus (&ge; 75)</td><td class="bold" style="color:#15803d;">: ' . $totalLulus . ' siswa (' . number_format(($totalLulus / max($sudahCount, 1)) * 100, 1) . '%)</td></tr>';
                echo '<tr><td class="sum-label">Tidak Lulus (&lt; 75)</td><td class="bold" style="color:#b91c1c;">: ' . $totalTidakLulus . ' siswa (' . number_format(($totalTidakLulus / max($sudahCount, 1)) * 100, 1) . '%)</td></tr>';
                echo '</table>';

                echo '<div class="footer-note">Dokumen ini digenerate otomatis oleh sistem pada ' . date('d-m-Y H:i:s') . ' WIB</div>';
                echo '</body></html>';
                exit;

            } else {
                // Format CSV
                $filename = 'nilai_' . $suffix . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: no-cache');

                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                $csvCols = ['No', 'Username', 'Nama Lengkap', 'Kelas', 'Ujian'];
                if (!$isBreakdownMode) $csvCols[] = 'Mata Pelajaran';
                $csvCols[] = 'Nilai';

                if ($isBreakdownMode) {
                    foreach ($dynamicHeaders as $dh) {
                        if ($dh['is_tkp']) {
                            $csvCols[] = "Skor (" . $dh['name'] . ")";
                        } else {
                            $csvCols[] = "Benar (" . $dh['name'] . ")";
                            $csvCols[] = "Salah (" . $dh['name'] . ")";
                        }
                    }
                } else {
                    $csvCols[] = 'Benar';
                    $csvCols[] = 'Salah';
                    $csvCols[] = 'Total Soal';
                }
                $csvCols[] = 'Waktu Mulai';
                $csvCols[] = 'Waktu Selesai';
                $csvCols[] = 'Keterangan';

                fputcsv($output, $csvCols);

                $no = 1;
                foreach ($results as $r) {
                    $sudah = $r['sudah_ujian'];
                    $nilai = (float)$r['nilai'];
                    $lulus = $sudah && $nilai >= 75;

                    $row = [
                        $no++,
                        $r['username'] ?? '',
                        $r['full_name'] ?? '',
                        $r['kelas'] ?? '',
                        $r['ujian'] ?? '',
                    ];
                    if (!$isBreakdownMode) $row[] = $r['mata_pelajaran'] ?? '';
                    $row[] = $sudah ? number_format($nilai, 2, '.', '') : '-';

                    if ($isBreakdownMode) {
                        foreach ($dynamicHeaders as $dh) {
                            $sid = $dh['id'];
                            if ($dh['is_tkp']) {
                                $row[] = $sudah ? (int)($r['skor_tkp_per_mapel'][$sid] ?? 0) : '-';
                            } else {
                                $benar = $sudah ? (int)($r['benar_per_mapel'][$sid] ?? 0) : 0;
                                $totalMapel = count(array_filter($questions, function ($q) use ($sid) {
                                    return ($q['subject_id'] == $sid) && !in_array($q['type'], ['essay', 'tkp'], true);
                                }));
                                $salah = max(0, $totalMapel - $benar);
                                $row[] = $sudah ? $benar : '-';
                                $row[] = $sudah ? $salah : '-';
                            }
                        }
                    } else {
                        $totalSoal   = (int)$r['total_soal'];
                        $jumlahBenar = (int)$r['jumlah_benar'];
                        $jumlahSalah = max(0, $totalSoal - $jumlahBenar);

                        $row[] = $sudah ? $jumlahBenar : '-';
                        $row[] = $sudah ? $jumlahSalah : '-';
                        $row[] = $totalSoal;
                    }

                    $row[] = $r['waktu_mulai'] ?? '';
                    $row[] = $r['waktu_selesai'] ?? '';
                    $row[] = $sudah ? ($lulus ? 'Lulus' : 'Tidak Lulus') : 'Belum Ujian';

                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
            }

        } catch (\Throwable $e) {
            if (ob_get_level()) ob_end_clean();
            error_log("Export nilai FATAL ERROR: " . $e->getMessage() . " di " . $e->getFile() . ":" . $e->getLine());
            error_log($e->getTraceAsString());
            $_SESSION['error'] = 'Gagal export nilai: ' . $e->getMessage() . ' (baris ' . $e->getLine() . ')';
            redirect('dashboard');
        }
    }

    public function previewExport()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $user = auth();
        if ($user['role'] === 'siswa') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $examId = $_GET['exam_id'] ?? null;
            $kelas = $_GET['kelas'] ?? null;

            $query = "
                SELECT 
                    u.username, u.full_name, u.kelas, e.title as ujian,
                    COALESCE(s.name, '') as mata_pelajaran, COALESCE(es.score, 0) as nilai,
                    COALESCE(es.started_at, '') as waktu_mulai, COALESCE(es.submitted_at, '') as waktu_selesai,
                    es.status, (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as jumlah_jawaban
                FROM exam_sessions es
                JOIN users u ON es.user_id = u.id
                JOIN exams e ON es.exam_id = e.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE (
                    es.status IN ('submitted', 'graded', 'completed', 'suspended')
                    OR (es.status = 'in_progress' AND e.end_time < NOW())
                )
                AND (SELECT COUNT(*) FROM answers WHERE session_id = es.id) > 0
            ";

            $params = [];
            if ($examId) { $query .= " AND es.exam_id = ?"; $params[] = $examId; }
            if ($kelas)  { $query .= " AND u.kelas = ?";   $params[] = $kelas; }
            $query .= " ORDER BY u.kelas, u.full_name, es.submitted_at DESC LIMIT 100";

            $stmt = db()->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => count($results), 'data' => $results]);
            exit;

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function nilaiZero()
    {
        requireAuth();
        $user = auth();

        if ($user['role'] === 'siswa') {
            $_SESSION['error'] = 'Anda tidak memiliki akses';
            redirect('dashboard');
        }

        try {
            $scoreExpr = self::scoreExprSql('a', 'q');

            $stmt = db()->prepare("
                SELECT 
                    es.id as session_id, u.id as user_id, u.username, u.full_name, u.kelas,
                    e.id as exam_id, e.title as ujian, COALESCE(s.name, '') as mata_pelajaran,
                    es.score, es.submitted_at,
                    (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as jumlah_jawaban,
                    (SELECT COALESCE(SUM({$scoreExpr}), 0)
                    FROM answers a JOIN questions q ON a.question_id = q.id
                    WHERE a.session_id = es.id) as nilai_recovery
                FROM exam_sessions es
                JOIN users u ON es.user_id = u.id
                JOIN exams e ON es.exam_id = e.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE es.score = 0
                AND es.status IN ('submitted', 'graded', 'completed', 'suspended')
                AND es.submitted_at IS NOT NULL
                ORDER BY u.kelas, u.full_name, es.submitted_at DESC
            ");
            $stmt->execute();
            $nilaiZero = $stmt->fetchAll();

            ob_start();
            include __DIR__ . '/../../views/dashboard/nilai-zero.php';
            $output = ob_get_clean();
            echo $output;

        } catch (\Exception $e) {
            error_log("Nilai Zero error: " . $e->getMessage());
            $_SESSION['error'] = 'Gagal memuat data: ' . $e->getMessage();
            redirect('dashboard');
        }
    }

    public function detailNilaiZero()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $user = auth();
        if ($user['role'] === 'siswa') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $sessionId = $_GET['session_id'] ?? null;
            if (!$sessionId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session ID tidak ditemukan']);
                exit;
            }

            $stmt = db()->prepare("
                SELECT es.id, es.user_id, es.score, es.submitted_at, u.full_name, u.kelas, e.title as ujian
                FROM exam_sessions es
                JOIN users u ON es.user_id = u.id
                JOIN exams e ON es.exam_id = e.id
                WHERE es.id = ? AND es.score = 0
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();

            if (!$session) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session tidak ditemukan']);
                exit;
            }

            // Tambahkan q.type untuk deteksi TKP yang lebih akurat
            $stmt = db()->prepare("
                SELECT 
                    a.id as answer_id, q.id as question_id, q.question_text,
                    q.correct_answer, q.points, q.option_scores, q.type as question_type,
                    a.answer as student_answer
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                WHERE a.session_id = ?
                ORDER BY q.id ASC
            ");
            $stmt->execute([$sessionId]);
            $answers = $stmt->fetchAll();

            $totalScore = 0;
            $correctCount = 0;
            $tkpScore = 0; // Perekapan khusus nilai TKP
            
            foreach ($answers as &$answer) {
                [$score, $isCorrect] = self::calcAnswerScore(
                    $answer['option_scores'],
                    $answer['student_answer'],
                    (float)$answer['points'],
                    $answer['correct_answer']
                );
                $answer['score_earned'] = $score;
                $answer['is_correct'] = $isCorrect ? 1 : 0;
                
                // Deteksi soal TKP: memiliki option_scores ATAU tipe soal adalah 'tkp'
                $isTkp = !empty($answer['option_scores']) || (isset($answer['question_type']) && strtolower(trim($answer['question_type'])) === 'tkp');
                if ($isTkp) {
                    $tkpScore += $score;
                }
                
                $totalScore += $score;
                if ($isCorrect) $correctCount++;
            }
            unset($answer);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'session' => $session,
                'answers' => $answers,
                'summary' => [
                    'total_questions' => count($answers),
                    'correct_answers' => $correctCount,
                    'calculated_score' => $totalScore,
                    'tkp_score' => $tkpScore // Dikirim ke frontend untuk perekapan
                ]
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Detail nilai zero error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }

    public function fixNilaiZero()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $user = auth();
        if ($user['role'] === 'siswa') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $sessionId = $_POST['session_id'] ?? null;
            $useRecovery = $_POST['use_recovery'] ?? false;

            if (!$sessionId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session ID tidak ditemukan']);
                exit;
            }

            $stmt = db()->prepare("
                SELECT es.id, es.user_id, es.score, u.full_name, e.title
                FROM exam_sessions es
                JOIN users u ON es.user_id = u.id
                JOIN exams e ON es.exam_id = e.id
                WHERE es.id = ? AND es.score = 0
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();

            if (!$session) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session tidak ditemukan atau bukan nilai 0']);
                exit;
            }

            $nilaiBaru = 0;

            if ($useRecovery) {
                $scoreExpr = self::scoreExprSql('a', 'q');
                $stmt = db()->prepare("
                    SELECT COALESCE(SUM({$scoreExpr}), 0) as calculated_score
                    FROM answers a JOIN questions q ON a.question_id = q.id
                    WHERE a.session_id = ?
                ");
                $stmt->execute([$sessionId]);
                $result = $stmt->fetch();
                $nilaiBaru = $result['calculated_score'] ?? 0;

                if ($nilaiBaru == 0) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Tidak ada jawaban yang tersimpan untuk recovery']);
                    exit;
                }
            } else {
                $nilaiBaruStr = $_POST['nilai_baru'] ?? null;
                if (!$nilaiBaruStr) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Nilai tidak ditemukan']);
                    exit;
                }

                $nilaiBaru = (float)$nilaiBaruStr;
                if ($nilaiBaru < 0 || $nilaiBaru > 100) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Nilai harus antara 0-100']);
                    exit;
                }
            }

            $stmt = db()->prepare("UPDATE exam_sessions SET score = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$nilaiBaru, $sessionId]);

            if ($result) {
                $method = $useRecovery ? 'recovery' : 'manual';
                error_log("Fix nilai 0: Session $sessionId updated from 0 to $nilaiBaru (method: $method) by user " . $user['id']);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Nilai berhasil diperbaiki',
                    'data' => [
                        'session_id' => $sessionId,
                        'student' => $session['full_name'],
                        'exam' => $session['title'],
                        'nilai_lama' => $session['score'],
                        'nilai_baru' => $nilaiBaru,
                        'method' => $method
                    ]
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Gagal update database']);
            }
            exit;

        } catch (\Exception $e) {
            error_log("Fix nilai error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }

    public function searchExams()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isLoggedIn() || auth()['role'] === 'siswa') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $keyword = trim($_GET['q'] ?? '');
        if ($keyword === '') {
            echo json_encode([]);
            exit;
        }

        $stmt = db()->prepare("
            SELECT id, title
            FROM exams
            WHERE title LIKE ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute(['%' . $keyword . '%']);
        echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    public function getKelas()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $user = auth();
        if ($user['role'] === 'siswa') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $examId = $_GET['exam_id'] ?? null;

        if ($examId) {
            // Hanya kelas milik siswa yang benar-benar di-assign sebagai peserta
            // (exam_participants) ATAU sudah punya sesi ujian (exam_sessions) untuk
            // ujian ini. Ini mencegah dropdown menampilkan SEMUA kelas se-sistem
            // padahal ujian yang dipilih cuma untuk kelas tertentu.
            $stmt = db()->prepare("
                SELECT DISTINCT u.kelas
                FROM users u
                WHERE u.kelas IS NOT NULL AND u.kelas != ''
                AND (
                    u.id IN (SELECT user_id FROM exam_participants WHERE exam_id = ? AND user_id IS NOT NULL)
                    OR u.id IN (SELECT user_id FROM exam_sessions WHERE exam_id = ? AND user_id IS NOT NULL)
                )
                ORDER BY u.kelas
            ");
            $stmt->execute([$examId, $examId]);
            $namaKelas = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $stmt = db()->query("SELECT DISTINCT kelas FROM users WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas");
            $namaKelas = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        
        header('Content-Type: application/json');
        echo json_encode($namaKelas);
        exit;
    }

    /**
     * Kembalikan daftar mapel.
     * Jika ?exam_id= diberikan -> HANYA mapel yang benar-benar dimasukkan
     * ke ujian tersebut (mendukung ujian multi-mapel via tabel exam_subjects,
     * dengan fallback ke exam_questions/questions, lalu ke mapel utama exams.subject_id).
     * Jika tidak ada exam_id -> semua mapel (perilaku lama, untuk kompatibilitas).
     */
    public function getSubjects()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $user = auth();
        if ($user['role'] === 'siswa') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $examId = $_GET['exam_id'] ?? null;

        if ($examId) {
            // 1) Sumber utama: tabel exam_subjects (dipakai untuk ujian multi-mapel)
            $stmt = db()->prepare("
                SELECT DISTINCT s.id, s.name
                FROM exam_subjects es
                JOIN subjects s ON s.id = es.subject_id
                WHERE es.exam_id = ?
                ORDER BY es.order_num ASC, s.name ASC
            ");
            $stmt->execute([$examId]);
            $daftarSubject = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 2) Fallback: mapel dari soal-soal yang benar-benar sudah dipasang ke ujian ini
            if (empty($daftarSubject)) {
                $stmt = db()->prepare("
                    SELECT DISTINCT s.id, s.name
                    FROM exam_questions eq
                    JOIN questions q ON q.id = eq.question_id
                    JOIN subjects s ON s.id = COALESCE(eq.subject_id, q.subject_id)
                    WHERE eq.exam_id = ?
                    ORDER BY s.name ASC
                ");
                $stmt->execute([$examId]);
                $daftarSubject = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // 3) Fallback terakhir: mapel utama ujian (untuk ujian tipe single)
            if (empty($daftarSubject)) {
                $stmt = db()->prepare("
                    SELECT s.id, s.name
                    FROM exams e
                    JOIN subjects s ON s.id = e.subject_id
                    WHERE e.id = ?
                ");
                $stmt->execute([$examId]);
                $daftarSubject = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } else {
            $stmt = db()->query("SELECT id, name FROM subjects ORDER BY name ASC");
            $daftarSubject = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        header('Content-Type: application/json');
        echo json_encode($daftarSubject);
        exit;
    }

    public function syncRecovery()
    {
        requireAuth();
        $user = auth();

        if ($user['role'] === 'siswa') {
            $_SESSION['error'] = 'Anda tidak memiliki akses';
            redirect('dashboard');
        }

        try {
            $examId = $_GET['exam_id'] ?? null;
            $kelas = $_GET['kelas'] ?? null;
            $scoreExpr = self::scoreExprSql('a', 'q');

            $query = "
                SELECT 
                    es.id as session_id, u.id as user_id, u.username, u.full_name, u.kelas,
                    e.id as exam_id, e.title as ujian, COALESCE(s.name, '') as mata_pelajaran,
                    es.score as nilai_saat_ini, es.submitted_at,
                    (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as jumlah_jawaban,
                    (SELECT COALESCE(SUM({$scoreExpr}), 0)
                    FROM answers a JOIN questions q ON a.question_id = q.id
                    WHERE a.session_id = es.id) as nilai_recovery
                FROM exam_sessions es
                JOIN users u ON es.user_id = u.id
                JOIN exams e ON es.exam_id = e.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE es.status IN ('submitted', 'graded', 'completed', 'suspended')
                AND es.submitted_at IS NOT NULL
                AND (SELECT COUNT(*) FROM answers WHERE session_id = es.id) > 0
            ";

            $params = [];
            if ($examId) { $query .= " AND es.exam_id = ?"; $params[] = $examId; }
            if ($kelas)  { $query .= " AND u.kelas = ?";   $params[] = $kelas; }
            $query .= " ORDER BY u.kelas, u.full_name, es.submitted_at DESC";

            $stmt = db()->prepare($query);
            $stmt->execute($params);
            $syncData = $stmt->fetchAll();

            require_once __DIR__ . '/../../views/dashboard/sync-recovery.php';

        } catch (\Exception $e) {
            error_log("Sync Recovery error: " . $e->getMessage());
            $_SESSION['error'] = 'Gagal memuat data: ' . $e->getMessage();
            redirect('dashboard');
        }
    }

    public function applySyncRecovery()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $user = auth();
        if ($user['role'] === 'siswa') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $sessionIds = $_POST['session_ids'] ?? [];
            if (empty($sessionIds)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Tidak ada session yang dipilih']);
                exit;
            }

            $updated = 0;
            $failed = 0;
            $scoreExpr = self::scoreExprSql('a', 'q');

            foreach ($sessionIds as $sessionId) {
                try {
                    $stmt = db()->prepare("
                        SELECT COALESCE(SUM({$scoreExpr}), 0) as calculated_score
                        FROM answers a JOIN questions q ON a.question_id = q.id
                        WHERE a.session_id = ?
                    ");
                    $stmt->execute([$sessionId]);
                    $result = $stmt->fetch();
                    $recoveryValue = $result['calculated_score'] ?? 0;

                    if ($recoveryValue > 0) {
                        $ustmt = db()->prepare("UPDATE exam_sessions SET score = ?, updated_at = NOW() WHERE id = ?");
                        if ($ustmt->execute([$recoveryValue, $sessionId])) {
                            $updated++;
                            error_log("Sync recovery: Session $sessionId updated to $recoveryValue by user " . $user['id']);
                        } else {
                            $failed++;
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                    error_log("Sync recovery error for session $sessionId: " . $e->getMessage());
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Sync selesai: $updated berhasil, $failed gagal",
                'updated' => $updated,
                'failed' => $failed
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Apply sync recovery error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }

    public function auditAktivitas()
    {
        requireAuth();
        $user = auth();

        if ($user['role'] === 'siswa') {
            $_SESSION['error'] = 'Anda tidak memiliki akses';
            redirect('dashboard');
        }

        try {
            $examId = $_GET['exam_id'] ?? null;
            $kelas = $_GET['kelas'] ?? null;

            $query = "
                SELECT 
                    es.id as session_id, u.id as user_id, u.username, u.full_name, u.kelas,
                    e.id as exam_id, e.title as ujian, COALESCE(s.name, '') as mata_pelajaran,
                    es.score, es.status, es.started_at, es.submitted_at,
                    (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as jumlah_jawaban
                FROM exam_sessions es
                JOIN users u ON es.user_id = u.id
                JOIN exams e ON es.exam_id = e.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE es.status IN ('submitted', 'graded', 'completed', 'suspended', 'in_progress')
            ";

            $params = [];
            if ($examId) { $query .= " AND es.exam_id = ?"; $params[] = $examId; }
            if ($kelas)  { $query .= " AND u.kelas = ?";   $params[] = $kelas; }
            $query .= " ORDER BY u.kelas, u.full_name, es.started_at DESC";

            $stmt = db()->prepare($query);
            $stmt->execute($params);
            $auditData = $stmt->fetchAll();

            $examStmt = db()->query("SELECT id, title FROM exams ORDER BY created_at DESC");
            $allExams = $examStmt->fetchAll();

            $kelasStmt = db()->query("SELECT DISTINCT kelas FROM users WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas");
            $allKelas = $kelasStmt->fetchAll(\PDO::FETCH_COLUMN);

            ob_start();
            include __DIR__ . '/../../views/dashboard/audit-aktivitas.php';
            $output = ob_get_clean();
            echo $output;

        } catch (\Exception $e) {
            error_log("Audit Aktivitas error: " . $e->getMessage());
            $_SESSION['error'] = 'Gagal memuat data: ' . $e->getMessage();
            redirect('dashboard');
        }
    }

    public function detailAuditAktivitas()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $user = auth();
        if ($user['role'] === 'siswa') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $sessionId = $_GET['session_id'] ?? null;
            if (!$sessionId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session ID tidak ditemukan']);
                exit;
            }

            $stmt = db()->prepare("
                SELECT es.id, es.user_id, es.score, es.status, es.started_at, es.submitted_at,
                       u.full_name, u.kelas, e.title as ujian
                FROM exam_sessions es
                JOIN users u ON es.user_id = u.id
                JOIN exams e ON es.exam_id = e.id
                WHERE es.id = ?
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();

            if (!$session) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session tidak ditemukan']);
                exit;
            }

            // Tambahkan q.type untuk deteksi TKP yang lebih akurat
            $stmt = db()->prepare("
                SELECT 
                    a.id as answer_id, q.id as question_id, q.question_text,
                    q.correct_answer, q.points, q.option_scores, q.type as question_type,
                    a.answer as student_answer, a.created_at as waktu_jawab
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                WHERE a.session_id = ?
                ORDER BY a.created_at ASC
            ");
            $stmt->execute([$sessionId]);
            $answers = $stmt->fetchAll();

            $stmt = db()->prepare("
                SELECT es.id, es.score, es.status, es.started_at, es.submitted_at,
                       (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as jumlah_jawaban
                FROM exam_sessions es
                WHERE es.user_id = ? AND es.exam_id = (SELECT exam_id FROM exam_sessions WHERE id = ?)
                ORDER BY es.started_at DESC
            ");
            $stmt->execute([$session['user_id'], $sessionId]);
            $allSessions = $stmt->fetchAll();

            $totalScore = 0;
            $correctCount = 0;
            $tkpScore = 0; // Perekapan khusus nilai TKP
            
            foreach ($answers as &$answer) {
                [$score, $isCorrect] = self::calcAnswerScore(
                    $answer['option_scores'],
                    $answer['student_answer'],
                    (float)$answer['points'],
                    $answer['correct_answer']
                );
                $answer['score_earned'] = $score;
                $answer['is_correct'] = $isCorrect ? 1 : 0;
                
                // Deteksi soal TKP: memiliki option_scores ATAU tipe soal adalah 'tkp'
                $isTkp = !empty($answer['option_scores']) || (isset($answer['question_type']) && strtolower(trim($answer['question_type'])) === 'tkp');
                if ($isTkp) {
                    $tkpScore += $score;
                }
                
                $totalScore += $score;
                if ($isCorrect) $correctCount++;
            }
            unset($answer);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'session' => $session,
                'answers' => $answers,
                'all_sessions' => $allSessions,
                'summary' => [
                    'total_questions' => count($answers),
                    'correct_answers' => $correctCount,
                    'calculated_score' => $totalScore,
                    'tkp_score' => $tkpScore, // Dikirim ke frontend untuk perekapan
                    'total_sessions' => count($allSessions)
                ]
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Detail audit aktivitas error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // method tambah akun baru
    public function createAccount()
    {
        requireAuth();
        requireRole(['admin']);
    
        header('Content-Type: application/json');
    
        try {
            $fullName  = trim($_POST['full_name'] ?? '');
            $role      = trim($_POST['role'] ?? '');
            $kelas     = trim($_POST['kelas'] ?? '');
            $subjectId = $_POST['subject_id'] ?? null;
    
            $allowedRoles = ['admin', 'guru', 'siswa', 'yayasan', 'staff', 'calon-siswa'];
    
            if ($fullName === '' || mb_strlen($fullName) < 3) {
                throw new \Exception('Nama lengkap wajib diisi, minimal 3 karakter.');
            }
            if (!in_array($role, $allowedRoles, true)) {
                throw new \Exception('Role tidak valid.');
            }
            if (($role === 'siswa' || $role === 'calon-siswa') && $kelas === '') {
                throw new \Exception('Kelas wajib diisi untuk role siswa.');
            }
            if ($role === 'guru') {
                if (empty($subjectId) || !is_numeric($subjectId)) {
                    throw new \Exception('Mata pelajaran wajib dipilih untuk role guru.');
                }
                $stmtSubj = db()->prepare("SELECT id FROM subjects WHERE id = ?");
                $stmtSubj->execute([$subjectId]);
                if (!$stmtSubj->fetch()) {
                    throw new \Exception('Mata pelajaran tidak ditemukan.');
                }
            }
    
            $localPart = $this->generateUsernameLocalPart($fullName);
    
            $domainMap = [
                'siswa'       => 'student.ubkn.sch.id',
                'calon-siswa' => 'student.ubkn.sch.id',
                'guru'        => 'teacher.ubkn.sch.id',
                'admin'       => 'staff.ubkn.sch.id',
                'yayasan'     => 'staff.ubkn.sch.id',
                'staff'       => 'staff.ubkn.sch.id',
            ];
            $domain = $domainMap[$role] ?? 'staff.ubkn.sch.id';
    
            // Pastikan username unik
            $username  = $localPart . '@' . $domain;
            $suffix    = 2;
            $stmtCheck = db()->prepare("SELECT id FROM users WHERE username = ?");
            while (true) {
                $stmtCheck->execute([$username]);
                if (!$stmtCheck->fetch()) break;
                $username = $localPart . $suffix . '@' . $domain;
                $suffix++;
            }
    
            $passwordMap = [
                'siswa'       => 'Siswa123!',
                'calon-siswa' => 'Siswa123!',
                'guru'        => 'Guru123!',
            ];
            $plainPassword  = $passwordMap[$role] ?? 'Welcome123!';
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    
            $kelasValue = ($role === 'siswa' || $role === 'calon-siswa') ? ($kelas ?: null) : null;
            $subjectIdValue = ($role === 'guru') ? (int) $subjectId : null;
    
            $stmt = db()->prepare("
                INSERT INTO users (username, password, full_name, kelas, role, subject_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$username, $hashedPassword, $fullName, $kelasValue, $role, $subjectIdValue]);
    
            echo json_encode([
                'success'  => true,
                'message'  => 'Akun berhasil dibuat.',
                'username' => $username,
                'password' => $plainPassword,
            ]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    private function generateUsernameLocalPart(string $fullName): string
    {
        $clean = preg_replace('/[^A-Za-z\s]/', '', $fullName);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        $words = array_values(array_filter(explode(' ', $clean), function ($w) {
            return $w !== '';
        }));
    
        if (count($words) === 0) return 'user';
        if (count($words) === 1) return strtolower($words[0]);
    
        $first = strtolower($words[0]);
        $last  = strtolower($words[count($words) - 1]);
        return $first . '.' . $last;
    }
}