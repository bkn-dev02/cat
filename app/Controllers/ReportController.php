<?php

namespace App\Controllers;

class ReportController
{
    public function laporanNilai()
    {
        requireAuth();

        $user = auth();
        if ($user['role'] === 'siswa') {
            $_SESSION['error'] = 'Akses ditolak';
            redirect('dashboard');
        }

        $examId    = $_GET['exam_id'] ?? null;
        $kelas     = $_GET['kelas'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;

        // Fallback: Jika exam_id tidak ada, tapi subject_id dan kelas ada, ambil ujian terbaru yang dipublish
        if (!$examId && $subjectId && $kelas) {
            $stmt = db()->prepare("
                SELECT e.id FROM exams e
                WHERE e.subject_id = ? AND e.status = 'published'
                ORDER BY e.created_at DESC LIMIT 1
            ");
            $stmt->execute([$subjectId]);
            $examId = $stmt->fetchColumn();
        }

        if (!$examId) {
            $_SESSION['error'] = 'Pilih ujian terlebih dahulu';
            redirect('dashboard');
        }

        // Info ujian
        $stmt = db()->prepare("
            SELECT e.*, s.name as subject_name
            FROM exams e
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE e.id = ?
        ");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();

        if (!$exam) {
            $_SESSION['error'] = 'Ujian tidak ditemukan';
            redirect('dashboard');
        }

        // ── PERBAIKAN: Query soal dengan filter subject_id & urutan sesuai preview ──
        $questionQuery = "
            SELECT q.id, q.type, q.question_text, q.correct_answer, q.option_scores, q.points,
                   eq.order_num as question_number, q.subject_id, q.created_at
            FROM exam_questions eq
            JOIN questions q ON q.id = eq.question_id
            WHERE eq.exam_id = ?
        ";
        $qParams = [$examId];

        if ($subjectId) {
            $questionQuery .= " AND q.subject_id = ?";
            $qParams[] = $subjectId;
        }

        // URUTAN SAMA PERSIS DENGAN PREVIEW & TAKE
        $questionQuery .= " ORDER BY q.created_at DESC, eq.id ASC";

        $qStmt = db()->prepare($questionQuery);
        $qStmt->execute($qParams);
        $questions = $qStmt->fetchAll();

        // ─ AMBIL SEMUA MAPEL YANG ADA DI UJIAN INI (untuk breakdown) ─
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

        // ── Query siswa ─────────────────────────────────────────────
        $query = "
            SELECT 
                u.id         AS user_id,
                u.full_name,
                u.kelas,
                es.id        AS session_id,
                es.score     AS nilai,
                es.submitted_at,
                es.status
            FROM users u
            LEFT JOIN exam_sessions es 
                ON es.id = (
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

        if ($kelas) {
            $query .= " AND u.kelas = ?";
            $params[] = $kelas;
        }

        if (!$kelas) {
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
        $sessions = $stmt->fetchAll();

        if (empty($sessions)) {
            $_SESSION['error'] = 'Tidak ada data siswa untuk ujian ini';
            redirect('dashboard');
        }

        $answersBySession = answersForSessions(array_column($sessions, 'session_id'));

        // ── HITUNG BENAR PER MAPEL UNTUK SETIAP SISWA ────────────────
        foreach ($sessions as &$s) {
            $s['answers']    = [];
            $s['sudah_ujian'] = false;
            $s['benar_per_mapel'] = []; // Array: subject_id => jumlah_benar
            $s['skor_tkp_per_mapel'] = []; // Array: subject_id => total skor TKP

            // Inisialisasi 0 untuk semua mapel
            foreach ($allSubjectsInExam as $subj) {
                $s['benar_per_mapel'][$subj['id']] = 0;
                $s['skor_tkp_per_mapel'][$subj['id']] = 0;
            }

            if ($s['session_id']) {
                $s['answers'] = $answersBySession[$s['session_id']] ?? [];
                $s['sudah_ujian'] = ($s['nilai'] !== null) || count($s['answers']) > 0;

                // Hitung benar per mapel & skor TKP per mapel
                foreach ($questions as $q) {
                    $jwb   = strtolower(trim((string)($s['answers'][$q['id']] ?? '')));
                    $kunci = strtolower(trim((string)($q['correct_answer'] ?? '')));
                    $type  = $q['type'] ?? 'multiple_choice';
                    $sid   = $q['subject_id'] ?? null;
                    
                    if ($jwb === '' || $sid === null) continue;
                    
                    // Khusus TKP: hitung skor
                    if ($type === 'tkp' && !empty($q['option_scores'])) {
                        $scoresMap = json_decode($q['option_scores'], true);
                        if (is_array($scoresMap) && isset($scoresMap[$jwb])) {
                            $s['skor_tkp_per_mapel'][$sid] += (int)$scoresMap[$jwb];
                        }
                        continue; // TKP tidak masuk hitungan benar/salah
                    }
                    
                    if ($type === 'essay') continue;
                    
                    $isBenar = false;
                    if ($type === 'short_answer') {
                        if (is_numeric($jwb) && is_numeric($kunci)) {
                            $isBenar = abs((float)$jwb - (float)$kunci) < 0.0001;
                        } else {
                            $isBenar = $jwb === $kunci;
                        }
                    } elseif (in_array($type, ['listening', 'multiple_choice', 'short_answer_text'])) {
                        $isBenar = $jwb === $kunci;
                    } else {
                        $isBenar = $jwb === $kunci;
                    }

                    if ($isBenar) {
                        $s['benar_per_mapel'][$sid]++;
                    }
                }

                if ($s['nilai'] === null && count($s['answers']) > 0) {
                    $s['nilai'] = $this->hitungNilai($questions, $s['answers']);
                }
            }
        }
        unset($s);

        // ─ DATA UNTUK DROPDOWN FILTER DI VIEW ──────────────────────
        $stmtSubjects = db()->query("SELECT id, name FROM subjects ORDER BY name ASC");
        $subjects = $stmtSubjects->fetchAll();

        $stmtKelas = db()->query("SELECT DISTINCT kelas FROM users WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
        $kelasList = $stmtKelas->fetchAll(\PDO::FETCH_COLUMN);

        $stmtExams = db()->query("SELECT id, title FROM exams WHERE status = 'published' ORDER BY created_at DESC");
        $availableExams = $stmtExams->fetchAll();

        view('report/laporan-nilai', compact(
            'exam', 'questions', 'sessions', 'kelas', 'subjectId',
            'subjects', 'kelasList', 'availableExams', 'allSubjectsInExam'
        ));
    }

    private function hitungNilai(array $questions, array $answers): float
    {
        $totalSoal = count(array_filter($questions, fn($q) => $q['type'] !== 'essay'));
        if ($totalSoal === 0) return 0;

        $benar = 0;
        foreach ($questions as $q) {
            if ($q['type'] === 'essay') continue;
            $jwb   = strtolower(trim((string)($answers[$q['id']] ?? '')));
            $kunci = strtolower(trim((string)($q['correct_answer'] ?? '')));
            if ($jwb === '' || $kunci === '') continue;

            if ($q['type'] === 'short_answer' && is_numeric($jwb) && is_numeric($kunci)) {
                if (abs((float)$jwb - (float)$kunci) < 0.0001) $benar++;
            } else {
                if ($jwb === $kunci) $benar++;
            }
        }

        return round(($benar / $totalSoal) * 100, 1);
    }
}