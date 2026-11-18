<?php

namespace App\Controllers;

use Exception;

class ExamController
{
    private const VIOLATION_LIMIT = 1;

    public function index()
    {
        requireAuth();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        try {
            $user = auth();
            $kelasUpper = strtoupper($user['kelas'] ?? '');
            $perPage = 24;
            $page    = max(1, (int)($_GET['page'] ?? 1));
            $offset  = ($page - 1) * $perPage;

            if (
                ($user['role'] === 'siswa' && $kelasUpper === 'CSR') ||
                $user['role'] === 'calon-siswa'
            ) {
                $activeTab = 'ppdb';
            } else {
                $activeTab = $_GET['tab'] ?? 'reguler';
                if (!in_array($activeTab, ['reguler', 'ppdb'])) $activeTab = 'reguler';
            }

            // ── Deteksi kolom opsional yang mungkin belum ada di DB lama ──────
            $hasExamType          = $this->columnExists('exams', 'exam_type');
            $hasRandomize         = $this->columnExists('exams', 'randomize_questions');
            $hasCategory          = $this->columnExists('exams', 'category');
            $hasCreatedBy         = $this->columnExists('exams', 'created_by');
            $examTypeCol      = $hasExamType    ? "e.exam_type"          : "'single' as exam_type";
            $randomizeCol     = $hasRandomize   ? "e.randomize_questions" : "0 as randomize_questions";
            $categoryWhere    = $hasCategory    ? "AND e.category = ?"    : "";
            $categoryOrder    = $hasCreatedBy   ? "AND e.created_by = ?"  : "";

            // ── Siswa & Calon-Siswa ──────────────────────────────────────────
            if ($user['role'] === 'siswa' || $user['role'] === 'calon-siswa') {

                if ($user['role'] === 'calon-siswa') {
                    $sql = "
                    SELECT e.id, e.title, e.duration, e.start_time, e.end_time, e.status,
                           {$examTypeCol}, {$randomizeCol},
                           s.name as subject_name,
                           CASE 
                               WHEN NOW() < e.start_time THEN 'upcoming'
                               WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'active'
                               ELSE 'ended'
                           END as exam_status,
                           1 as can_access,
                           CASE
                               WHEN EXISTS (
                                   SELECT 1 FROM exam_sessions es2
                                   WHERE es2.exam_id = e.id AND es2.user_id = ?
                                   AND (es2.status IN ('submitted','completed') OR es2.submitted_at IS NOT NULL)
                               ) THEN 1
                               ELSE 0
                           END as already_submitted
                    FROM exams e
                    LEFT JOIN subjects s ON e.subject_id = s.id
                    WHERE e.status = 'published'
                    AND e.end_time >= NOW()
                    {$categoryWhere}
                    ORDER BY e.start_time ASC
                ";
                    $params = $hasCategory ? [$user['id'], $activeTab] : [$user['id']];
                    $stmt = db()->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $kelasUser = $user['kelas'] ?? '';
                    $sql = "
                    SELECT e.id, e.title, e.duration, e.start_time, e.end_time, e.status,
                           {$examTypeCol}, {$randomizeCol},
                           s.name as subject_name,
                           CASE 
                               WHEN NOW() < e.start_time THEN 'upcoming'
                               WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'active'
                               ELSE 'ended'
                           END as exam_status,
                           CASE 
                               WHEN ep.exam_id IS NOT NULL THEN 1
                               WHEN NOT EXISTS (SELECT 1 FROM exam_participants ep0 WHERE ep0.exam_id = e.id) THEN 1
                               WHEN ? != '' AND EXISTS (
                                   SELECT 1 FROM exam_participants ep2
                                   JOIN users u2 ON u2.id = ep2.user_id
                                   WHERE ep2.exam_id = e.id AND u2.kelas = ?
                               ) THEN 1
                               ELSE 0
                           END as can_access,
                           CASE
                               WHEN EXISTS (
                                   SELECT 1 FROM exam_sessions es2
                                   WHERE es2.exam_id = e.id AND es2.user_id = ?
                                   AND (es2.status IN ('submitted','completed') OR es2.submitted_at IS NOT NULL)
                               ) THEN 1
                               ELSE 0
                           END as already_submitted
                    FROM exams e
                    LEFT JOIN subjects s ON e.subject_id = s.id
                    LEFT JOIN exam_participants ep ON e.id = ep.exam_id AND ep.user_id = ?
                    WHERE e.status = 'published'
                    AND e.end_time >= NOW()
                    {$categoryWhere}
                    ORDER BY e.start_time ASC
                ";
                    $params = [$kelasUser, $kelasUser, $user['id'], $user['id']];
                    if ($hasCategory) $params[] = $activeTab;
                    $stmt = db()->prepare($sql);
                    $stmt->execute($params);
                }

                $allExams = $stmt->fetchAll();
                $exams = [];
                foreach ($allExams as $exam) {
                    if ($user['role'] === 'calon-siswa' || $exam['can_access']) {
                        $exams[] = $exam;
                    }
                }

                // Pagination untuk siswa (filter can_access dilakukan di PHP)
                $totalExams = count($exams);
                $exams = array_slice($exams, $offset, $perPage);
            } elseif ($user['role'] === 'admin') {
                $sql = "
                SELECT e.id, e.title, e.duration, e.start_time, e.end_time, e.status,
                       e.exam_pdf,
                       {$examTypeCol}, {$randomizeCol},
                       s.name as subject_name,
                       " . ($hasCreatedBy ? "COALESCE(u.full_name, u.username) as creator_name," : "NULL as creator_name,") . "
                       CASE 
                           WHEN NOW() < e.start_time THEN 'upcoming'
                           WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'active'
                           ELSE 'ended'
                       END as exam_status
               FROM exams e
               LEFT JOIN subjects s ON e.subject_id = s.id
               " . ($hasCreatedBy ? "LEFT JOIN users u ON e.created_by = u.id" : "") . "
               " . ($hasCategory ? "WHERE e.category = ?" : "") . "
               ORDER BY e.created_at DESC
           ";
                $params = $hasCategory ? [$activeTab] : [];

                // Hitung total untuk pagination
                $countSql = "SELECT COUNT(*) FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id " . ($hasCategory ? "WHERE e.category = ?" : "");
                $stmtCount = db()->prepare($countSql);
                $stmtCount->execute($params);
                $totalExams = (int)$stmtCount->fetchColumn();

                // Tambah LIMIT/OFFSET
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = $perPage;
                $params[] = $offset;
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                $exams = $stmt->fetchAll();
            } else {
                // Guru
                $sql = "
                SELECT e.id, e.title, e.duration, e.start_time, e.end_time, e.status,
                       e.exam_pdf,
                       {$examTypeCol}, {$randomizeCol},
                       s.name as subject_name,
                       CASE 
                           WHEN NOW() < e.start_time THEN 'upcoming'
                           WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'active'
                           ELSE 'ended'
                       END as exam_status
               FROM exams e
               LEFT JOIN subjects s ON e.subject_id = s.id
               WHERE 1=1
               " . ($hasCategory  ? "AND e.category = ?"   : "") . "
               " . ($hasCreatedBy ? "AND e.created_by = ?" : "") . "
               ORDER BY e.created_at DESC
           ";
                $params = [];
                if ($hasCategory)  $params[] = $activeTab;
                if ($hasCreatedBy) $params[] = $user['id'];

                // Hitung total untuk pagination
                $countSql = "SELECT COUNT(*) FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id WHERE 1=1 " . ($hasCategory ? "AND e.category = ?" : "") . ($hasCreatedBy ? "AND e.created_by = ?" : "");
                $stmtCount = db()->prepare($countSql);
                $stmtCount->execute($params);
                $totalExams = (int)$stmtCount->fetchColumn();

                // Tambah LIMIT/OFFSET
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = $perPage;
                $params[] = $offset;
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                $exams = $stmt->fetchAll();
            }

            if ($user['role'] !== 'siswa' && $exams) {
                $examIds = array_column($exams, 'id');
                $placeholders = implode(',', array_fill(0, count($examIds), '?'));
                $stmt = db()->prepare("
                SELECT
                    ep.exam_id,
                    COUNT(DISTINCT u.kelas) AS class_count,
                    GROUP_CONCAT(DISTINCT u.kelas ORDER BY u.kelas SEPARATOR ', ') AS classes
                FROM exam_participants ep
                JOIN users u ON ep.user_id = u.id
                WHERE ep.exam_id IN ($placeholders)
                  AND u.role = 'siswa'
                GROUP BY ep.exam_id
            ");
                $stmt->execute($examIds);
                $participantInfoByExam = [];
                foreach ($stmt->fetchAll() as $participantInfo) {
                    $participantInfoByExam[$participantInfo['exam_id']] = $participantInfo;
                }

                foreach ($exams as &$exam) {
                    $exam['participant_class_count'] = (int)($participantInfoByExam[$exam['id']]['class_count'] ?? 0);
                    $exam['participant_classes'] = $participantInfoByExam[$exam['id']]['classes'] ?? '';
                }
                unset($exam);
            }

            $totalPages = max(1, (int)ceil($totalExams / $perPage));
            $page       = min($page, $totalPages);

            view('exams/index', [
                'exams'     => $exams,
                'user'      => $user,
                'activeTab' => $activeTab,
                'page'      => $page,
                'totalPages' => $totalPages,
                'perPage'   => $perPage,
                'totalExams' => $totalExams,
            ]);
        } catch (\Exception $e) {
            error_log('[ExamController::index] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $errMsg = htmlspecialchars($e->getMessage());
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Error</title>
            <style>body{font-family:sans-serif;background:#1e293b;color:#e2e8f0;padding:2rem;min-height:100vh;margin:0}
            .box{background:#0f172a;border:1px solid #f59e0b;border-radius:12px;padding:2rem;max-width:700px;margin:2rem auto}
            h2{color:#f59e0b}pre{background:#1e293b;padding:1rem;border-radius:8px;overflow:auto;font-size:.8rem;color:#f87171}
            a{display:inline-block;margin-top:1rem;background:#3b82f6;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;margin-right:8px}</style>
            </head><body><div class="box">
            <h2>&#9888; Error Memuat Daftar Ujian</h2>
            <p>Detail error (kirim ke admin):</p>
            <pre>' . $errMsg . '</pre>
            <a href="javascript:location.reload()">&#8635; Coba Lagi</a>
            <a href="' . config('app_url') . '/auth/logout" style="background:#ef4444">Logout</a>
            </div></body></html>';
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (isset($cache[$key])) return $cache[$key];
        try {
            $stmt = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            $cache[$key] = (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }

    public function uploadPdf()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $examId = $_POST['exam_id'] ?? null;

        if (!$examId) {
            echo json_encode(['success' => false, 'message' => 'Exam ID tidak valid']);
            return;
        }

        if (!isset($_FILES['exam_pdf']) || $_FILES['exam_pdf']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File PDF tidak ditemukan atau gagal diupload']);
            return;
        }

        $file = $_FILES['exam_pdf'];

        // Validate file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'File harus berformat PDF']);
            return;
        }

        // Validate size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 10MB']);
            return;
        }

        try {
            // Verify exam exists
            $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $exam = $stmt->fetch();

            if (!$exam) {
                echo json_encode(['success' => false, 'message' => 'Ujian tidak ditemukan']);
                return;
            }

            // FIX: path langsung ke uploads/exams (tanpa folder "public")
            $uploadDir = __DIR__ . '/../../uploads/exams/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'exam_' . $examId . '_' . time() . '.pdf';
            $destination = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file ke ' . $destination]);
                return;
            }

            // Delete old PDF if exists
            if (!empty($exam['exam_pdf'])) {
                $oldPath = $uploadDir . $exam['exam_pdf'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Update exam record
            $stmt = db()->prepare("UPDATE exams SET exam_pdf = ? WHERE id = ?");
            $stmt->execute([$filename, $examId]);

            echo json_encode(['success' => true, 'message' => 'PDF berhasil diupload']);
        } catch (\Exception $e) {
            error_log("UploadPdf error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload PDF: ' . $e->getMessage()]);
        }
    }

    public function deletePdf($examId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $exam = $stmt->fetch();

            if (!$exam) {
                echo json_encode(['success' => false, 'message' => 'Ujian tidak ditemukan']);
                return;
            }

            if (empty($exam['exam_pdf'])) {
                echo json_encode(['success' => false, 'message' => 'Tidak ada PDF untuk dihapus']);
                return;
            }

            $uploadDir = __DIR__ . '/../../uploads/exams/';
            $filePath  = $uploadDir . $exam['exam_pdf'];

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $stmt = db()->prepare("UPDATE exams SET exam_pdf = NULL WHERE id = ?");
            $stmt->execute([$examId]);

            echo json_encode(['success' => true, 'message' => 'PDF berhasil dihapus']);
        } catch (\Exception $e) {
            error_log("DeletePdf error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus PDF: ' . $e->getMessage()]);
        }
    }

    public function create()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title              = $_POST['title']           ?? '';
            $subjectId          = $_POST['subject_id']      ?? '';
            $duration           = (int)($_POST['duration']  ?? 0);
            $startTime          = fromDatetimeLocal($_POST['start_time'] ?? '');
            $endTime            = fromDatetimeLocal($_POST['end_time']   ?? '');
            $randomizeQuestions = isset($_POST['randomize_questions']) ? 1 : 0;
            $examType           = $_POST['exam_type']       ?? 'single';
            $category           = in_array($_POST['category'] ?? '', ['reguler', 'ppdb']) ? $_POST['category'] : 'reguler';
            $participantType    = $_POST['participant_type'] ?? 'all';
            $selectedClasses    = $_POST['selected_classes'] ?? [];

            if (empty($title) || empty($duration) || empty($startTime) || empty($endTime)) {
                $_SESSION['error'] = 'Semua field wajib diisi';
            } elseif ($participantType === 'class' && empty($selectedClasses)) {
                $_SESSION['error'] = 'Pilih minimal 1 kelas untuk ujian terbatas';
            } else {
                try {
                    $user = auth();

                    db()->beginTransaction();

                    // Generate unique token
                    $token = 'EXAM-' . strtoupper(substr(md5(uniqid() . time()), 0, 10));

                    $stmt = db()->prepare("
                        INSERT INTO exams (title, category, subject_id, duration, start_time, end_time, token, randomize_questions, exam_type, status, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW())
                    ");
                    $stmt->execute([$title, $category, $subjectId ?: null, $duration, $startTime, $endTime, $token, $randomizeQuestions, $examType, $user['id']]);
                    $newExamId = (int)db()->lastInsertId();

                    // ── Tambahkan peserta berdasarkan kelas yang dipilih ──────────
                    if ($participantType === 'class' && !empty($selectedClasses) && $newExamId > 0) {
                        $ph = str_repeat('?,', count($selectedClasses) - 1) . '?';
                        $stmtStudents = db()->prepare("
                            SELECT id FROM users
                            WHERE role = 'siswa' AND kelas IN ($ph)
                        ");
                        $stmtStudents->execute($selectedClasses);
                        $students = $stmtStudents->fetchAll();

                        if (!empty($students)) {
                            $stmtIns = db()->prepare("
                                INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)
                            ");
                            foreach ($students as $student) {
                                $stmtIns->execute([$newExamId, $student['id']]);
                            }
                        }
                    }

                    db()->commit();
                    $_SESSION['success'] = 'Ujian berhasil dibuat';
                    redirect('exam');
                    return;
                } catch (\Exception $e) {
                    db()->rollBack();
                    $_SESSION['error'] = 'Gagal membuat ujian: ' . $e->getMessage();
                }
            }
        }

        // Get subjects
        $stmt = db()->prepare("SELECT * FROM subjects ORDER BY name");
        $stmt->execute();
        $subjects = $stmt->fetchAll();

        view('exams/create', ['subjects' => $subjects]);
    }

    public function previewQuestions()
    {
        header('Content-Type: application/json');

        requireAuth();
        requireRole(['admin', 'guru']);

        $user = auth();

        // Ambil examId dari URL
        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        $examId = (int) end($parts);

        if (!$examId) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            return;
        }

        try {
            $stmt = db()->prepare("
                SELECT eq.*, q.*, s.name as subject_name
                FROM exam_questions eq
                JOIN questions q ON q.id = eq.question_id
                LEFT JOIN subjects s ON q.subject_id = s.id
                WHERE eq.exam_id = ?
                ORDER BY q.created_at ASC, eq.id ASC
            ");
            $stmt->execute([$examId]);
            $questions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'questions' => $questions]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function start($examId)
    {
        try {
            requireAuth();
            requireRole(['siswa']);

            $user = auth();

            // Get exam
            $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $exam = $stmt->fetch();

            if (!$exam) {
                $_SESSION['error'] = 'Ujian tidak ditemukan';
                redirect('exam');
                return;
            }

            // ── Validasi status ujian ──────────────────────────────────────
            // Harus published
            if ($exam['status'] !== 'published') {
                $_SESSION['error'] = 'Ujian belum tersedia.';
                redirect('exam');
                return;
            }

            $now  = time();
            $examStart = strtotime($exam['start_time']);
            $examEnd   = strtotime($exam['end_time']);

            // Belum waktunya
            if ($now < $examStart) {
                $_SESSION['error'] = 'Ujian belum dimulai. Ujian akan dimulai pada ' . date('d/m/Y H:i', $examStart) . ' WIB.';
                redirect('exam');
                return;
            }

            // Sudah berakhir
            if ($now > $examEnd) {
                $_SESSION['error'] = 'Waktu ujian sudah berakhir.';
                redirect('exam');
                return;
            }
            if ($user['role'] !== 'calon-siswa') {
                $stmtAccess = db()->prepare("
                    SELECT COUNT(*) as total FROM exam_participants WHERE exam_id = ?
                ");
                $stmtAccess->execute([$examId]);
                $hasRestriction = (int)$stmtAccess->fetchColumn() > 0;

                if ($hasRestriction) {
                    // Cek 1: siswa terdaftar langsung di exam_participants
                    $stmtAllowed = db()->prepare("
                        SELECT id FROM exam_participants WHERE exam_id = ? AND user_id = ?
                    ");
                    $stmtAllowed->execute([$examId, $user['id']]);
                    $directAccess = $stmtAllowed->fetch();

                    if (!$directAccess) {
                        // Cek 2: kelasnya ada di antara kelas peserta ujian ini
                        // Ini menangani siswa yang bergabung ke kelas SETELAH ujian dibuat
                        $stmtKelas = db()->prepare("
                            SELECT COUNT(*) FROM exam_participants ep
                            JOIN users u ON u.id = ep.user_id
                            WHERE ep.exam_id = ?
                              AND u.kelas = ?
                              AND u.role = 'siswa'
                        ");
                        $stmtKelas->execute([$examId, $user['kelas'] ?? '']);
                        $kelasAccess = (int)$stmtKelas->fetchColumn() > 0;

                        if (!$kelasAccess) {
                            $_SESSION['error'] = 'Anda tidak memiliki akses ke ujian ini.';
                            redirect('exam');
                            return;
                        }

                        // Auto-tambahkan siswa ke exam_participants agar check berikutnya valid
                        try {
                            $stmtInsert = db()->prepare("
                                INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)
                            ");
                            $stmtInsert->execute([$examId, $user['id']]);
                        } catch (Exception $e) {
                            // Silent — bukan error fatal
                        }
                    }
                }
            }

            // Check if already have session yang masih active (bukan submitted/completed)
            $stmt = db()->prepare("
                SELECT * FROM exam_sessions 
                WHERE exam_id = ? AND user_id = ?
                AND status NOT IN ('submitted', 'completed')
                AND submitted_at IS NULL
            ");
            $stmt->execute([$examId, $user['id']]);
            $session = $stmt->fetch();

            // Cek apakah sudah pernah submit ujian ini
            $stmtDone = db()->prepare("
                SELECT id FROM exam_sessions 
                WHERE exam_id = ? AND user_id = ?
                AND (status IN ('submitted', 'completed') OR submitted_at IS NOT NULL)
                LIMIT 1
            ");
            $stmtDone->execute([$examId, $user['id']]);
            $doneSession = $stmtDone->fetch();

            if ($doneSession) {
                $_SESSION['error'] = 'Anda sudah mengerjakan ujian ini. Ujian hanya dapat dikerjakan satu kali.';
                redirect('exam');
                return;
            }

            if (!$session) {
                // Generate unique session token
                $sessionToken = bin2hex(random_bytes(32));

                // Individual exam duration only — not capped by global access window end time
                $examDuration = $exam['duration'] * 60; // in seconds

                // Ensure remaining time is not negative
                $remainingTime = max(0, $examDuration);

                // Create new session with full individual duration
                $stmt = db()->prepare("
                    INSERT INTO exam_sessions (exam_id, user_id, remaining_time, session_token, token_status, status, started_at)
                    VALUES (?, ?, ?, ?, 'active', 'active', NOW())
                ");
                $stmt->execute([$examId, $user['id'], $remainingTime, $sessionToken]);
                $sessionId = db()->lastInsertId();
            } else {
                $sessionId = $session['id'];

                // Jika sesi sedang suspended, validasi global access window sebelum resume
                if ($session['status'] === 'suspended') {
                    $now = time();
                    $examEnd = strtotime($exam['end_time']);
                    if ($now > $examEnd) {
                        $_SESSION['error'] = 'Waktu akses ujian sudah berakhir. Tidak dapat melanjutkan ujian.';
                        redirect('exam');
                        return;
                    }
                }

                // Pertahankan remaining_time (durasi individual), jangan hitung ulang berdasarkan end_time
                // Catat waktu resume aktual sebagai awal akumulasi aktif berikutnya
                $stmt = db()->prepare("
                    UPDATE exam_sessions 
                    SET started_at = NOW(), status = 'active', token_status = 'active'
                    WHERE id = ?
                ");

                $stmt->execute([$sessionId]);
            }

            redirect('exam/take/' . $sessionId);
        } catch (Exception $e) {
            error_log("Start exam error: " . $e->getMessage());
            $_SESSION['error'] = 'Terjadi kesalahan saat memulai ujian. Silakan coba lagi.';
            redirect('exam');
        }
    }

    public function take($sessionId)
    {
        requireAuth();

        $stmt = db()->prepare("
            SELECT es.*,
                   e.title,
                   e.duration,
                   e.end_time,
                   e.subject_id,
                   COALESCE(e.randomize_questions, 0) as randomize_questions,
                   COALESCE(e.exam_type, 'single') as exam_type,
                   e.exam_pdf
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.id = ?
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session) {
            $_SESSION['error'] = 'Session ujian tidak ditemukan';
            redirect('exam');
            return;
        }

        // Check if this session belongs to current user OR if user is admin/guru
        $user = auth();
        if ($session['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'guru'])) {
            $_SESSION['error'] = 'Anda tidak memiliki akses ke session ini';
            redirect('exam');
            return;
        }
        if (!empty($session['force_submitted']) && $session['status'] === 'completed') {
            redirect('exam/result/' . $sessionId);
            return;
        }

        // A suspended regular exam must remain available so its local answer queue can sync.
        if ($session['status'] === 'completed' || (
            $session['status'] === 'suspended'
            && (!empty($session['exam_pdf']) || (int)$session['user_id'] !== (int)$user['id'])
        )) {
            redirect('exam/result/' . $sessionId);
            return;
        }

        // Check if session is submitted (hanya redirect ke result jika status submitted DAN ada submitted_at)
        if ($session['status'] === 'submitted' && !empty($session['submitted_at'])) {
            redirect('exam/result/' . $sessionId);
            return;
        }

        // Validasi global access window untuk sesi suspended: resume ditolak jika window sudah tutup
        if ($session['status'] === 'suspended') {
            $now = time();
            $examEnd = strtotime($session['end_time']);
            if ($now > $examEnd) {
                $_SESSION['error'] = 'Waktu akses ujian sudah berakhir. Tidak dapat melanjutkan ujian.';
                redirect('exam');
                return;
            }
        }

        // Jika status active/in_progress, SELALU tampilkan soal — jangan redirect ke result

        if (!in_array($session['status'], ['active', 'in_progress', 'suspended', 'submitted', 'completed'], true)) {
            $stmt = db()->prepare("
                UPDATE exam_sessions 
                SET status = 'active', token_status = 'active', started_at = COALESCE(started_at, NOW())
                WHERE id = ?
            ");
            $stmt->execute([$sessionId]);
            $session['status'] = 'active';
            $session['token_status'] = 'active';
        }

        // If exam has PDF, show PDF view
        if (!empty($session['exam_pdf'])) {
            // Get exam data
            $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$session['exam_id']]);
            $exam = $stmt->fetch();

            view('exams/take_pdf', [
                'exam' => $exam,
                'session' => $session,
                'user' => auth()
            ]);
            return;
        }

        // Regular exam with questions
        $stmt = db()->prepare("
            SELECT q.id, q.subject_id, q.type, q.question_text, q.question_image,
                   q.audio_file, q.max_plays, q.pdf_file, q.question_number,
                   q.option_a, q.option_b, q.option_c, q.option_d, q.option_e,
                   q.correct_answer, q.hide_correct_answer, q.points, q.created_at,
                   eq.subject_id as exam_subject_id, s.name as subject_name,
                   COALESCE(es.order_num, 0) as subject_order
            FROM questions q
            JOIN exam_questions eq ON q.id = eq.question_id
            LEFT JOIN subjects s ON eq.subject_id = s.id
            LEFT JOIN exam_subjects es ON es.exam_id = eq.exam_id AND es.subject_id = eq.subject_id
            WHERE eq.exam_id = ?
            ORDER BY q.created_at ASC, eq.id ASC
        ");
        $stmt->execute([$session['exam_id']]);
        $questions = $stmt->fetchAll();

        // Randomize questions if enabled (but keep subject grouping for multi-subject)
        if ($session['randomize_questions'] == 1) {
            if ($session['exam_type'] === 'multi') {
                // Group by subject, randomize within each group
                $grouped = [];
                foreach ($questions as $q) {
                    $grouped[$q['exam_subject_id']][] = $q;
                }
                $questions = [];
                foreach ($grouped as $subjectQuestions) {
                    shuffle($subjectQuestions);
                    $questions = array_merge($questions, $subjectQuestions);
                }
            } else {
                shuffle($questions);
            }
        }

        // Get existing answers
        $stmt = db()->prepare("
            SELECT question_id, answer FROM answers WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);
        $answers = [];
        foreach ($stmt->fetchAll() as $row) {
            $answers[$row['question_id']] = $row['answer'];
        }

        view('exams/take', [
            'session' => $session,
            'questions' => $questions,
            'answers' => $answers,
            'isSuspended' => $session['status'] === 'suspended'
        ]);
    }

    public function saveAnswer()
    {
        requireAuth();
        set_time_limit(30);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $sessionId  = $_POST['session_id'] ?? '';
        $questionId = $_POST['question_id'] ?? '';
        $answer = isset($_POST['answer']) ? $_POST['answer'] : '';

        if (empty($sessionId) || empty($questionId)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        try {
            $user = auth();
            $stmt = db()->prepare("
                SELECT id, status, submitted_at
                FROM exam_sessions
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$sessionId, $user['id']]);
            $session = $stmt->fetch();

            if (!$session) {
                echo json_encode(['success' => false, 'message' => 'Session tidak valid']);
                return;
            }

            if ($session['submitted_at'] !== null || in_array($session['status'], ['submitted', 'completed'], true)) {
                echo json_encode(['success' => false, 'message' => 'Ujian sudah dikumpulkan, jawaban tidak dapat diubah']);
                return;
            }

            // Check if answer exists
            $stmt = db()->prepare("SELECT id FROM answers WHERE session_id = ? AND question_id = ?");
            $stmt->execute([$sessionId, $questionId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing answer
                $stmt = db()->prepare("UPDATE answers SET answer = ?, updated_at = NOW() WHERE session_id = ? AND question_id = ?");
                $stmt->execute([$answer, $sessionId, $questionId]);
            } else {
                // Insert new answer
                $stmt = db()->prepare("INSERT INTO answers (session_id, question_id, answer, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$sessionId, $questionId, $answer]);
            }

            echo json_encode(['success' => true, 'message' => 'Jawaban tersimpan']);
        } catch (Exception $e) {
            error_log("SaveAnswer Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan jawaban: ' . $e->getMessage()]);
        }
    }

    public function saveAnswers()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            requireAuth();
            $user = auth();
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                return;
            }

            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Format permintaan tidak valid']);
                return;
            }

            $sessionId = filter_var($payload['session_id'] ?? null, FILTER_VALIDATE_INT);
            $submittedAnswers = $payload['answers'] ?? null;

            if (!$sessionId || !is_array($submittedAnswers) || !$submittedAnswers || count($submittedAnswers) > 50) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Data jawaban tidak valid']);
                return;
            }

            $answersByQuestion = [];
            foreach ($submittedAnswers as $item) {
                if (!is_array($item)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Format jawaban tidak valid']);
                    return;
                }

                $questionId = filter_var($item['question_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$questionId || !array_key_exists('answer', $item) || !is_string($item['answer'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Format jawaban tidak valid']);
                    return;
                }

                $answersByQuestion[$questionId] = $item['answer'];
            }

            $pdo = db();
            $pdo->beginTransaction();

            $stmtSession = $pdo->prepare("
                SELECT exam_id, status, submitted_at
                FROM exam_sessions
                WHERE id = ? AND user_id = ?
                FOR UPDATE
            ");
            $stmtSession->execute([$sessionId, $user['id']]);
            $session = $stmtSession->fetch();

            if (!$session) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sesi ujian tidak ditemukan']);
                return;
            }

            if (!in_array($session['status'], ['active', 'in_progress', 'suspended'], true) || $session['submitted_at'] !== null) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesi ujian sudah dikumpulkan atau tidak dapat menerima jawaban',
                    'session_status' => $session['status'],
                ]);
                return;
            }

            $questionIds = array_keys($answersByQuestion);
            $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
            $stmtQuestions = $pdo->prepare("
                SELECT DISTINCT question_id
                FROM exam_questions
                WHERE exam_id = ? AND question_id IN ({$placeholders})
            ");
            $stmtQuestions->execute(array_merge([$session['exam_id']], $questionIds));
            $validQuestionIds = array_map('intval', $stmtQuestions->fetchAll(\PDO::FETCH_COLUMN));

            if (count($validQuestionIds) !== count($questionIds)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ada soal yang bukan bagian dari ujian ini']);
                return;
            }

            $stmtExisting = $pdo->prepare("
                SELECT id, question_id
                FROM answers
                WHERE session_id = ? AND question_id IN ({$placeholders})
                FOR UPDATE
            ");
            $stmtExisting->execute(array_merge([$sessionId], $questionIds));

            $existingAnswerIds = [];
            foreach ($stmtExisting->fetchAll() as $row) {
                $existingAnswerIds[(int)$row['question_id']][] = (int)$row['id'];
            }

            $updateCases = [];
            $updateParams = [];
            $updateIds = [];
            $insertValues = [];
            $insertParams = [];

            foreach ($answersByQuestion as $questionId => $answer) {
                if (!empty($existingAnswerIds[$questionId])) {
                    foreach ($existingAnswerIds[$questionId] as $answerId) {
                        $updateCases[] = 'WHEN ? THEN ?';
                        $updateParams[] = $answerId;
                        $updateParams[] = $answer;
                        $updateIds[] = $answerId;
                    }
                } else {
                    $insertValues[] = '(?, ?, ?, NOW(), NOW())';
                    array_push($insertParams, $sessionId, $questionId, $answer);
                }
            }

            if ($updateCases) {
                $updatePlaceholders = implode(',', array_fill(0, count($updateIds), '?'));
                $stmtUpdate = $pdo->prepare("
                    UPDATE answers
                    SET answer = CASE id " . implode(' ', $updateCases) . " ELSE answer END,
                        updated_at = NOW()
                    WHERE id IN ({$updatePlaceholders})
                ");
                $stmtUpdate->execute(array_merge($updateParams, $updateIds));
            }

            if ($insertValues) {
                $stmtInsert = $pdo->prepare(
                    "
                    INSERT INTO answers (session_id, question_id, answer, created_at, updated_at)
                    VALUES " . implode(',', $insertValues)
                );
                $stmtInsert->execute($insertParams);
            }

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'saved_question_ids' => $questionIds,
            ]);
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Save answers batch error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan jawaban']);
        }
    }

    public function saveTime()
    {
        requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? '';
        $remainingTime = $input['remaining_time'] ?? 0;

        if (empty($sessionId)) {
            echo json_encode(['success' => false, 'message' => 'Session ID tidak valid']);
            return;
        }

        try {
            // Verify session belongs to current user and get current server state
            $user = auth();
            $stmt = db()->prepare("
                SELECT id, remaining_time, status, started_at 
                FROM exam_sessions 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$sessionId, $user['id']]);
            $session = $stmt->fetch();
            if (!$session) {
                echo json_encode(['success' => false, 'message' => 'Session tidak valid']);
                return;
            }

            // Hitung sisa waktu yang diharapkan dari server (bukan dari klien)
            $savedRemaining = (int)$session['remaining_time'];
            $startedAt    = $session['started_at'] ? strtotime($session['started_at']) : time();
            $now          = time();
            $elapsed      = ($session['status'] === 'active') ? max(0, $now - $startedAt) : 0;
            $expectedRemaining = max(0, $savedRemaining - $elapsed);

            // Terima nilai klien hanya jika tidak melebihi harapan server (mencegah penambahan waktu ilegal)
            $clientRemaining = (int)$remainingTime;
            $finalRemaining  = min($clientRemaining, $expectedRemaining);

            // Update remaining time
            $stmt = db()->prepare("UPDATE exam_sessions SET remaining_time = ? WHERE id = ?");
            $stmt->execute([$finalRemaining, $sessionId]);

            echo json_encode(['success' => true, 'message' => 'Waktu tersimpan', 'remaining_time' => $finalRemaining]);
        } catch (Exception $e) {
            error_log("SaveTime Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan waktu: ' . $e->getMessage()]);
        }
    }

    public function submit($sessionId = null)
    {
        requireAuth();

        try {
            set_time_limit(30);

            // Get session ID from parameter or POST data
            if ($sessionId === null) {
                $sessionId = $_POST['session_id'] ?? null;
            }

            if (!$sessionId) {
                error_log("Submit error: No session ID provided");

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Session ID tidak ditemukan']);
                    return;
                }

                $_SESSION['error'] = 'Session ID tidak ditemukan';
                redirect('dashboard');
                return;
            }

            // Get session info
            $stmt = db()->prepare("
            SELECT es.*, e.id as exam_id, e.title
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.id = ? AND es.user_id = ?
        ");
            $stmt->execute([$sessionId, auth()['id']]);
            $session = $stmt->fetch();

            if (!$session) {
                error_log("Submit error: Session not found - ID: $sessionId, User: " . auth()['id']);

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Session ujian tidak ditemukan']);
                    return;
                }

                $_SESSION['error'] = 'Session ujian tidak ditemukan';
                redirect('dashboard');
                return;
            }

            $pdo = db();
            $pdo->beginTransaction();

            // Lock the session row to prevent concurrent submits
            $stmtLock = $pdo->prepare("
            SELECT id, status, submitted_at, exam_id
            FROM exam_sessions
            WHERE id = ? AND user_id = ?
            FOR UPDATE
        ");
            $stmtLock->execute([$sessionId, auth()['id']]);
            $lockedSession = $stmtLock->fetch();

            if (!$lockedSession) {
                $pdo->rollBack();
                throw new \Exception("Session tidak ditemukan");
            }

            // Guard: jika sudah submitted, jangan proses lagi
            if ($lockedSession['submitted_at'] !== null || in_array($lockedSession['status'], ['submitted', 'completed'], true)) {
                $pdo->rollBack();
                error_log("Submit skipped (already submitted) - Session: $sessionId");

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Ujian sudah dikumpulkan sebelumnya', 'session_id' => $sessionId]);
                    return;
                }
                redirect('exam/result/' . $sessionId . '?t=' . time());
                return;
            }

            // Get all answers with correct answers, points, AND type
            $stmt = $pdo->prepare("
            SELECT a.*, q.correct_answer, q.points, q.type, q.option_scores
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = ?
        ");
            $stmt->execute([$sessionId]);
            $answers = $stmt->fetchAll();

            $totalScore = 0;
            $correctCount = 0;
            foreach ($answers as $answer) {
                $score = $this->getAnswerScore(
                    $answer['type'],
                    $answer['answer'],
                    $answer['correct_answer'],
                    $answer['points'],
                    $answer['option_scores']
                );
                $totalScore += $score;
                if ($score > 0) $correctCount++;
            }

            error_log("Submit calculation - Session: $sessionId, Total answers: " . count($answers) . ", Correct: $correctCount, Score: $totalScore");

            // Update session with score — only if not yet submitted (double-guard)
            $stmt = $pdo->prepare("
            UPDATE exam_sessions 
            SET status = 'submitted', 
                submitted_at = NOW(), 
                score = ?, 
                is_best_score = 1 
            WHERE id = ? AND submitted_at IS NULL
        ");
            $result = $stmt->execute([$totalScore, $sessionId]);

            if ($stmt->rowCount() === 0) {
                // Another concurrent request already submitted this session
                $pdo->rollBack();
                error_log("Submit skipped (concurrent submit) - Session: $sessionId");

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Ujian sudah dikumpulkan', 'session_id' => $sessionId]);
                    return;
                }
                redirect('exam/result/' . $sessionId . '?t=' . time());
                return;
            }

            error_log("Submit success - Session: $sessionId updated with score: $totalScore");

            // Mark other submissions from same user-exam as not best
            $stmt = $pdo->prepare("
            UPDATE exam_sessions 
            SET is_best_score = 0 
            WHERE user_id = ? AND exam_id = ? AND id != ? 
            AND status IN ('submitted', 'graded', 'completed')
        ");
            $stmt->execute([auth()['id'], $session['exam_id'], $sessionId]);

            $pdo->commit();

            // If AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Ujian berhasil dikumpulkan',
                    'score' => $totalScore,
                    'session_id' => $sessionId
                ]);
                return;
            }

            $_SESSION['success'] = 'Ujian berhasil dikumpulkan!';
            redirect('exam/result/' . $sessionId . '?t=' . time());
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Submit error: " . $e->getMessage() . " - Session: " . ($sessionId ?? 'unknown'));

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Gagal mengumpulkan ujian: ' . $e->getMessage()]);
                return;
            }

            $_SESSION['error'] = 'Gagal mengumpulkan ujian: ' . $e->getMessage();
            redirect('dashboard');
        }
    }

    public function result($sessionId)
    {
        requireAuth();

        // Disable caching
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        // Get session and exam info with user data - FRESH from database
        $stmt = db()->prepare("
            SELECT es.*, e.title, e.duration,
                   COALESCE(e.exam_type, 'single') as exam_type,
                   s.name as subject_name,
                   u.username, u.full_name, u.kelas
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            JOIN users u ON es.user_id = u.id
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE es.id = ?
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session) {
            $_SESSION['error'] = 'Session tidak ditemukan';
            redirect('exam');
            return;
        }

        // Get answers and calculate score FRESH
        $stmt = db()->prepare("
        SELECT a.*, q.correct_answer, q.points, q.question_text, q.type, q.option_scores
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        WHERE a.session_id = ?
        ");
        $stmt->execute([$sessionId]);
        $answers = $stmt->fetchAll();

        $totalScore = 0;
        $correctAnswers = 0;
        $totalQuestions = count($answers);

        foreach ($answers as $answer) {
            $score = $this->getAnswerScore(
                $answer['type'],
                $answer['answer'],
                $answer['correct_answer'],
                $answer['points'],
                $answer['option_scores']
            );
            $totalScore += $score;
            if ($score > 0) $correctAnswers++;
        }

        // If calculated score is different from database, update it
        if ($totalScore != $session['score']) {
            $stmt = db()->prepare("UPDATE exam_sessions SET score = ?, is_best_score = 1 WHERE id = ?");
            $stmt->execute([$totalScore, $sessionId]);
            $session['score'] = $totalScore; // Update local variable
            error_log("Result page: Updated score for session $sessionId from {$session['score']} to $totalScore");
        }

        view('exams/result', [
            'session' => $session,
            'answers' => $answers,
            'totalScore' => $totalScore,
            'correctAnswers' => $correctAnswers,
            'totalQuestions' => $totalQuestions,
            'user' => auth(),
        ]);
    }

    public function edit($examId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $subjectId = $_POST['subject_id'] ?? '';
            $duration = (int)($_POST['duration'] ?? 0);
            $startTime = fromDatetimeLocal($_POST['start_time'] ?? '');
            $endTime = fromDatetimeLocal($_POST['end_time'] ?? '');
            $randomizeQuestions = isset($_POST['randomize_questions']) ? 1 : 0;
            $examType = $_POST['exam_type'] ?? 'single';
            $status = $_POST['status'] ?? 'draft';
            $participantType = $_POST['participant_type'] ?? 'all';
            $selectedClasses = $_POST['selected_classes'] ?? [];

            if (empty($title) || empty($duration) || empty($startTime) || empty($endTime)) {
                $_SESSION['error'] = 'Semua field wajib diisi';
            } else if ($participantType === 'class' && empty($selectedClasses)) {
                $_SESSION['error'] = 'Pilih minimal 1 kelas untuk ujian terbatas';
            } else if ($startTime && $endTime && strtotime($endTime) <= strtotime($startTime)) {
                $_SESSION['error'] = 'Waktu selesai harus lebih dari waktu mulai';
            } else {
                try {
                    db()->beginTransaction();

                    // Update exam data
                    $stmt = db()->prepare("
                        UPDATE exams 
                        SET title = ?, subject_id = ?, duration = ?, start_time = ?, end_time = ?, 
                            randomize_questions = ?, exam_type = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $subjectId ?: null, $duration, $startTime, $endTime, $randomizeQuestions, $examType, $status, $examId]);

                    // Handle participants
                    if ($participantType === 'all') {
                        // Remove all specific participants (make it public)
                        $stmt = db()->prepare("DELETE FROM exam_participants WHERE exam_id = ?");
                        $stmt->execute([$examId]);
                    } else {
                        // Remove existing participants
                        $stmt = db()->prepare("DELETE FROM exam_participants WHERE exam_id = ?");
                        $stmt->execute([$examId]);

                        // Add new participants based on selected classes
                        if (!empty($selectedClasses)) {
                            $placeholders = str_repeat('?,', count($selectedClasses) - 1) . '?';
                            // Ambil SEMUA siswa dari kelas terpilih (termasuk yang baru didaftarkan)
                            $stmt = db()->prepare("
                                SELECT id FROM users 
                                WHERE role = 'siswa' AND kelas IN ($placeholders)
                            ");
                            $stmt->execute($selectedClasses);
                            $students = $stmt->fetchAll();

                            if (!empty($students)) {
                                $stmt = db()->prepare("
                                    INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)
                                ");
                                foreach ($students as $student) {
                                    $stmt->execute([$examId, $student['id']]);
                                }
                            }
                        }
                    }

                    db()->commit();
                    $_SESSION['success'] = 'Ujian berhasil diperbarui';
                    redirect('exam');
                    return;
                } catch (Exception $e) {
                    db()->rollBack();
                    $_SESSION['error'] = 'Gagal memperbarui ujian: ' . $e->getMessage();
                }
            }
        }

        // Get exam data
        $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();

        if (!$exam) {
            $_SESSION['error'] = 'Ujian tidak ditemukan';
            redirect('exam');
            return;
        }

        // Get subjects
        $stmt = db()->prepare("SELECT * FROM subjects ORDER BY name");
        $stmt->execute();
        $subjects = $stmt->fetchAll();

        // Get exam subjects for multi-subject exams
        $examSubjects = [];
        if ($exam['exam_type'] === 'multi') {
            $stmt = db()->prepare("
                SELECT es.*, s.name as subject_name 
                FROM exam_subjects es 
                JOIN subjects s ON es.subject_id = s.id 
                WHERE es.exam_id = ?
                ORDER BY s.name
            ");
            $stmt->execute([$examId]);
            $examSubjects = $stmt->fetchAll();
        }

        view('exams/edit', [
            'exam' => $exam,
            'subjects' => $subjects,
            'examSubjects' => $examSubjects
        ]);
    }

    public function assignQuestions($examId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get exam info first to check exam type
            $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $exam = $stmt->fetch();

            if (!$exam) {
                $_SESSION['error'] = 'Ujian tidak ditemukan';
                redirect('exam');
                return;
            }

            $selectedQuestions = [];

            // Handle multi-subject exam
            if ($exam['exam_type'] === 'multi') {
                // Collect questions from all subjects
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'questions_') === 0 && is_array($value)) {
                        $selectedQuestions = array_merge($selectedQuestions, $value);
                    }
                }
            } else {
                // Handle single subject exam
                $selectedQuestions = $_POST['questions'] ?? [];
            }

            if (empty($selectedQuestions)) {
                $_SESSION['error'] = 'Pilih minimal satu soal';
            } else {
                try {
                    // Clear existing questions
                    $stmt = db()->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
                    $stmt->execute([$examId]);

                    // Add selected questions
                    $stmt = db()->prepare("INSERT INTO exam_questions (exam_id, question_id, order_num) VALUES (?, ?, ?)");
                    foreach ($selectedQuestions as $index => $questionId) {
                        $stmt->execute([$examId, $questionId, $index + 1]);
                    }

                    $_SESSION['success'] = 'Soal berhasil ditambahkan ke ujian';
                    redirect('exam');
                    return;
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Gagal menambahkan soal: ' . $e->getMessage();
                }
            }
        }

        // Get exam info (already retrieved in POST section if it was POST request)
        if (!isset($exam)) {
            $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $exam = $stmt->fetch();

            if (!$exam) {
                $_SESSION['error'] = 'Ujian tidak ditemukan';
                redirect('exam');
                return;
            }
        }

        // Check if exam already has questions
        $stmt = db()->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
        $stmt->execute([$examId]);
        $hasQuestions = $stmt->fetchColumn() > 0 || !empty($exam['exam_pdf']);

        // Get current user
        $user = auth();

        // If exam has no questions yet, show empty state with options
        if (!$hasQuestions) {
            // For new exams, show the assign interface with empty state
            $_SESSION['info'] = 'Ujian baru perlu ditambahkan soal terlebih dahulu sebelum bisa digunakan.';

            // Get all available questions for empty state
            // Guru hanya melihat soal miliknya sendiri; admin melihat semua soal
            $onlyMine = ($user['role'] === 'guru');
            $whereMine = $onlyMine ? "WHERE q.created_by = ?" : "";
            $stmt = db()->prepare("
                SELECT q.*, s.name as subject_name,
                       CASE WHEN q.created_by = ? THEN 1 ELSE 0 END as is_mine,
                       u.username as creator_name
                FROM questions q
                LEFT JOIN subjects s ON q.subject_id = s.id
                LEFT JOIN users u ON q.created_by = u.id
                {$whereMine}
                ORDER BY 
                    CASE WHEN q.created_by = ? THEN 0 ELSE 1 END,
                    s.name, 
                    q.created_at DESC
            ");
            $paramsQ = [$user['id']];
            if ($onlyMine) $paramsQ[] = $user['id'];
            $paramsQ[] = $user['id'];
            $stmt->execute($paramsQ);
            $questions = $stmt->fetchAll();

            // Get exam subjects for multi-subject exams
            $examSubjects = [];
            $questionsBySubject = [];

            if ($exam['exam_type'] === 'multi') {
                $stmt = db()->prepare("
                    SELECT es.*, s.name as subject_name 
                    FROM exam_subjects es 
                    JOIN subjects s ON es.subject_id = s.id 
                    WHERE es.exam_id = ? ORDER BY es.order_num
                ");
                $stmt->execute([$examId]);
                $examSubjects = $stmt->fetchAll();

                // Group questions by subject
                foreach ($questions as $q) {
                    if ($q['subject_id']) {
                        $questionsBySubject[$q['subject_id']][] = $q;
                    }
                }
            }

            view('exams/assign', [
                'exam' => $exam,
                'hasQuestions' => false,
                'allQuestions' => $questions,
                'questions' => $questions,
                'examSubjects' => $examSubjects,
                'questionsBySubject' => $questionsBySubject
            ]);
            return;
        }

        // If exam already has questions, show full interface
        // Guru hanya melihat soal miliknya sendiri; admin melihat semua soal
        $onlyMine = ($user['role'] === 'guru');
        $whereMine = $onlyMine ? "WHERE q.created_by = ?" : "";
        $stmt = db()->prepare("
            SELECT q.*, s.name as subject_name,
                   CASE WHEN eq.question_id IS NOT NULL THEN 1 ELSE 0 END as is_selected,
                   CASE WHEN q.created_by = ? THEN 1 ELSE 0 END as is_mine,
                   u.username as creator_name
            FROM questions q
            LEFT JOIN subjects s ON q.subject_id = s.id
            LEFT JOIN exam_questions eq ON q.id = eq.question_id AND eq.exam_id = ?
            LEFT JOIN users u ON q.created_by = u.id
            {$whereMine}
            ORDER BY 
                CASE WHEN q.created_by = ? THEN 0 ELSE 1 END,
                s.name, 
                q.created_at DESC
        ");
        $paramsQ = [$user['id'], $examId];
        if ($onlyMine) $paramsQ[] = $user['id'];
        $paramsQ[] = $user['id'];
        $stmt->execute($paramsQ);
        $questions = $stmt->fetchAll();

        // Separate user's questions from others
        $myQuestions = [];
        $otherQuestions = [];

        foreach ($questions as $q) {
            if ($q['is_mine']) {
                $myQuestions[] = $q;
            } else {
                $otherQuestions[] = $q;
            }
        }

        // Get exam subjects for multi-subject exams
        $examSubjects = [];
        $questionsBySubject = [];

        if ($exam['exam_type'] === 'multi') {
            $stmt = db()->prepare("
                SELECT es.*, s.name as subject_name 
                FROM exam_subjects es 
                JOIN subjects s ON es.subject_id = s.id 
                WHERE es.exam_id = ? ORDER BY es.order_num
            ");
            $stmt->execute([$examId]);
            $examSubjects = $stmt->fetchAll();

            // Group questions by subject
            foreach ($questions as $q) {
                if ($q['subject_id']) {
                    $questionsBySubject[$q['subject_id']][] = $q;
                }
            }
        }

        view('exams/assign', [
            'exam' => $exam,
            'hasQuestions' => true,
            'myQuestions' => $myQuestions,
            'otherQuestions' => $otherQuestions,
            'allQuestions' => $questions,
            'questions' => $questions, // Add this for backward compatibility
            'examSubjects' => $examSubjects,
            'questionsBySubject' => $questionsBySubject
        ]);
    }


    public function getAvailableQuestions($examId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        try {
            $user = auth();
            // Guru hanya melihat soal miliknya sendiri; admin melihat semua soal
            $onlyMine = ($user['role'] === 'guru');
            $whereMine = $onlyMine ? "WHERE q.created_by = ?" : "";
            $stmt = db()->prepare("
                SELECT q.*, s.name as subject_name,
                       CASE WHEN eq.question_id IS NOT NULL THEN 1 ELSE 0 END as is_selected,
                       CASE WHEN q.created_by = ? THEN 1 ELSE 0 END as is_mine,
                       u.username as creator_name
                FROM questions q
                LEFT JOIN subjects s ON q.subject_id = s.id
                LEFT JOIN exam_questions eq ON q.id = eq.question_id AND eq.exam_id = ?
                LEFT JOIN users u ON q.created_by = u.id
                {$whereMine}
                ORDER BY 
                    CASE WHEN q.created_by = ? THEN 0 ELSE 1 END,
                    s.name, 
                    q.created_at DESC
            ");
            $paramsQ = [$user['id'], $examId];
            if ($onlyMine) $paramsQ[] = $user['id'];
            $paramsQ[] = $user['id'];
            $stmt->execute($paramsQ);
            $questions = $stmt->fetchAll();

            // Generate HTML
            ob_start();
?>
            <form method="POST" style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div style="margin-bottom: 20px;">
                    <h5>Pilih Soal untuk Ujian</h5>
                    <p style="color: #64748b; font-size: 0.9rem;">Centang soal yang ingin digunakan dalam ujian ini.</p>
                </div>

                <?php if (empty($questions)): ?>
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <h5>Belum Ada Soal</h5>
                        <p>Belum ada soal yang tersedia. <a href="<?= config('app_url') ?>/question/create">Buat soal baru</a> terlebih dahulu.</p>
                    </div>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px;">
                        <?php foreach ($questions as $q): ?>
                            <div style="border-bottom: 1px solid #f1f5f9; padding: 15px 0; display: flex; align-items: flex-start; gap: 12px;">
                                <input type="checkbox"
                                    name="questions[]"
                                    value="<?= $q['id'] ?>"
                                    <?= ($q['is_selected'] ?? 0) > 0 ? 'checked' : '' ?>
                                    style="margin-top: 4px;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                                        <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                            <?= $q['type'] === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay' ?>
                                        </span>
                                        <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                            <?= $q['points'] ?> poin
                                        </span>
                                        <?php if ($q['is_mine']): ?>
                                            <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                                Soal Saya
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: #374151; margin-bottom: 5px;">
                                        <?= truncateHtml(renderQuestionText($q['question_text']), 150) ?>
                                    </div>
                                    <div style="color: #6b7280; font-size: 0.8rem;">
                                        Mata Pelajaran: <?= htmlspecialchars($q['subject_name'] ?? 'Tidak ada') ?>
                                        <?php if (!$q['is_mine']): ?>
                                            | Dibuat oleh: <?= htmlspecialchars($q['creator_name'] ?? 'Unknown') ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 20px; text-align: center;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">
                            <i class="fas fa-save"></i> Simpan Pilihan Soal
                        </button>
                    </div>
                <?php endif; ?>
            </form>
<?php
            $html = ob_get_clean();

            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function quickPublish($examId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $action = $_POST['action'] ?? 'publish';

        try {
            if ($action === 'fix') {
                // Fix exam by changing status to draft
                $stmt = db()->prepare("UPDATE exams SET status = 'draft' WHERE id = ?");
                $stmt->execute([$examId]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Exam status changed to draft'
                ]);
                return;
            }

            // Check if exam has questions before publishing
            $stmt = db()->prepare("SELECT exam_pdf FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $examPdf = $stmt->fetchColumn();

            $stmt = db()->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
            $stmt->execute([$examId]);
            $questionCount = $stmt->fetchColumn();

            if ($questionCount == 0 && empty($examPdf)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak bisa publish ujian tanpa soal. Tambahkan soal atau upload PDF terlebih dahulu.'
                ]);
                return;
            }

            // Update exam status to published
            $stmt = db()->prepare("UPDATE exams SET status = 'published' WHERE id = ?");
            $stmt->execute([$examId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Exam published successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Exam not found or already published'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    public function delete($examId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_delete'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        try {
            db()->beginTransaction();

            // Delete in correct order to avoid foreign key constraints
            $stmt = db()->prepare("DELETE FROM answers WHERE session_id IN (SELECT id FROM exam_sessions WHERE exam_id = ?)");
            $stmt->execute([$examId]);

            $stmt = db()->prepare("DELETE FROM exam_sessions WHERE exam_id = ?");
            $stmt->execute([$examId]);

            $stmt = db()->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
            $stmt->execute([$examId]);

            $stmt = db()->prepare("DELETE FROM exam_participants WHERE exam_id = ?");
            $stmt->execute([$examId]);

            $stmt = db()->prepare("DELETE FROM exam_subjects WHERE exam_id = ?");
            $stmt->execute([$examId]);

            $stmt = db()->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$examId]);

            db()->commit();

            echo json_encode(['success' => true, 'message' => 'Ujian berhasil dihapus']);
        } catch (Exception $e) {
            db()->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus ujian: ' . $e->getMessage()]);
        }
    }

    public function tokenRequests()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        // Get all token requests
        $stmt = db()->prepare("
            SELECT es.id, es.exam_id, es.user_id, es.status, es.token_status,
                   COALESCE(es.token_requested_at, es.started_at) AS requested_at,
                   es.started_at, es.suspended_at, es.suspension_reason,
                   es.violation_count,
                   u.username, u.full_name AS student_name, u.kelas,
                   e.title as exam_title, e.start_time, e.end_time
            FROM exam_sessions es
            JOIN users u ON es.user_id = u.id
            JOIN exams e ON es.exam_id = e.id
            WHERE es.token_status = 'requested'
            ORDER BY COALESCE(es.token_requested_at, es.started_at) DESC
        ");
        $stmt->execute();
        $requests = $stmt->fetchAll();

        view('exams/token_requests', [
            'requests' => $requests
        ]);
    }

    public function approveToken($sessionId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        try {
            // Update token status to active
            $stmt = db()->prepare("
                UPDATE exam_sessions
                SET token_status = 'active',
                    status = 'active',
                    started_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$sessionId]);

            echo json_encode(['success' => true, 'message' => 'Token berhasil disetujui']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyetujui token: ' . $e->getMessage()]);
        }
    }

    public function rejectToken($sessionId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        try {
            // Update token status to rejected
            $stmt = db()->prepare("
                UPDATE exam_sessions
                SET token_status = 'rejected',
                    status = 'suspended'
                WHERE id = ?
            ");
            $stmt->execute([$sessionId]);

            echo json_encode(['success' => true, 'message' => 'Token berhasil ditolak']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menolak token: ' . $e->getMessage()]);
        }
    }

    public function logViolation()
    {
        requireAuth();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $sessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT);
        $eventUuid = strtolower(trim((string)($_POST['event_uuid'] ?? '')));
        $reason = trim((string)($_POST['reason'] ?? ''));
        $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') : strlen($reason);
        if (
            $sessionId === false
            || $sessionId === null
            || $sessionId <= 0
            || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $eventUuid)
            || $reason === ''
            || $reasonLength > 255
        ) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data pelanggaran tidak valid']);
            return;
        }

        $pdo = null;
        try {
            $pdo = db();
            $user = auth();
            $pdo->beginTransaction();

            $stmtSession = $pdo->prepare("
                SELECT es.id, es.exam_id, es.status, es.submitted_at, e.exam_pdf
                FROM exam_sessions es
                JOIN exams e ON e.id = es.exam_id
                WHERE es.id = ? AND es.user_id = ?
                FOR UPDATE
            ");
            $stmtSession->execute([$sessionId, $user['id']]);
            $session = $stmtSession->fetch();
            if (!$session) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Session tidak valid']);
                return;
            }

            $stmtEvent = $pdo->prepare("
                SELECT id
                FROM exam_violations
                WHERE session_id = ? AND event_uuid = ?
                LIMIT 1
            ");
            $stmtEvent->execute([$sessionId, $eventUuid]);
            $eventAlreadyLogged = $stmtEvent->fetchColumn() !== false;
            if (
                !$eventAlreadyLogged
                && ($session['submitted_at'] !== null
                    || in_array($session['status'], ['submitted', 'completed'], true))
            ) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Ujian sudah dikumpulkan']);
                return;
            }

            $eventResult = $this->persistViolationEvent(
                $pdo,
                (int)$sessionId,
                (int)$user['id'],
                $eventUuid,
                $reason
            );
            $autoSubmitted = false;
            if (
                $eventResult['count'] >= self::VIOLATION_LIMIT
                && in_array($session['status'], ['active', 'in_progress'], true)
                && $session['submitted_at'] === null
            ) {
                $stmtSuspend = $pdo->prepare("
                    UPDATE exam_sessions
                    SET status = 'suspended',
                        suspended_at = COALESCE(suspended_at, NOW()),
                        suspension_reason = ?
                    WHERE id = ? AND submitted_at IS NULL
                ");
                $stmtSuspend->execute(['Pelanggaran ujian: ' . $reason, $sessionId]);

                $autoSubmitted = $this->autoSubmitViolationSession(
                    $pdo,
                    $session,
                    (int)$user['id'],
                    'Auto-submit: pelanggaran - ' . $reason
                );
            }

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => $eventResult['inserted'] ? 'Violation logged' : 'Violation already logged',
                'violation_count' => $eventResult['count'],
                'auto_submitted' => $autoSubmitted,
            ]);
        } catch (Exception $e) {
            if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Log violation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to log violation']);
        }
    }

    private function persistViolationEvent(
        \PDO $pdo,
        int $sessionId,
        int $userId,
        string $eventUuid,
        string $reason
    ): array {
        $stmtEvent = $pdo->prepare("
            SELECT id
            FROM exam_violations
            WHERE session_id = ? AND event_uuid = ?
            LIMIT 1
        ");
        $stmtEvent->execute([$sessionId, $eventUuid]);
        $eventExists = $stmtEvent->fetchColumn() !== false;
        $inserted = false;

        if (!$eventExists) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM exam_violations WHERE session_id = ?");
            $stmtCount->execute([$sessionId]);
            $nextCount = (int)$stmtCount->fetchColumn() + 1;

            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO exam_violations
                        (session_id, user_id, event_uuid, reason, violation_count, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmtInsert->execute([$sessionId, $userId, $eventUuid, $reason, $nextCount]);
                $inserted = true;
            } catch (\PDOException $e) {
                if ((int)($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }

                $stmtEvent->execute([$sessionId, $eventUuid]);
                if ($stmtEvent->fetchColumn() === false) {
                    throw $e;
                }
            }
        }

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM exam_violations WHERE session_id = ?");
        $stmtCount->execute([$sessionId]);
        $violationCount = (int)$stmtCount->fetchColumn();

        $stmtUpdate = $pdo->prepare("UPDATE exam_sessions SET violation_count = ? WHERE id = ?");
        $stmtUpdate->execute([$violationCount, $sessionId]);

        return [
            'inserted' => $inserted,
            'count' => $violationCount,
        ];
    }

    private function calculateSessionScore(\PDO $pdo, int $sessionId)
    {
        $stmtAnswers = $pdo->prepare("
            SELECT a.answer, q.correct_answer, q.points, q.type, q.option_scores
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = ?
        ");
        $stmtAnswers->execute([$sessionId]);

        $totalScore = 0;
        foreach ($stmtAnswers->fetchAll() as $answer) {
            $totalScore += $this->getAnswerScore(
                $answer['type'],
                $answer['answer'],
                $answer['correct_answer'],
                $answer['points'],
                $answer['option_scores']
            );
        }

        return $totalScore;
    }

    private function autoSubmitViolationSession(
        \PDO $pdo,
        array $session,
        int $userId,
        string $reason,
        bool $clearOtherBestScores = false
    ): bool {
        $totalScore = $this->calculateSessionScore($pdo, (int)$session['id']);
        $stmtSubmit = $pdo->prepare("
            UPDATE exam_sessions
            SET score = ?,
                is_best_score = 1,
                suspension_reason = ?
            WHERE id = ? AND submitted_at IS NULL
        ");
        $stmtSubmit->execute([$totalScore, $reason, $session['id']]);
        $autoSubmitted = $stmtSubmit->rowCount() > 0;

        if ($autoSubmitted && $clearOtherBestScores) {
            $stmtOtherScores = $pdo->prepare("
                UPDATE exam_sessions
                SET is_best_score = 0
                WHERE user_id = ? AND exam_id = ? AND id != ?
                AND status IN ('submitted', 'graded', 'completed')
            ");
            $stmtOtherScores->execute([$userId, $session['exam_id'], $session['id']]);
        }

        return $autoSubmitted;
    }

    public function suspendSession()
    {
        requireAuth();

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        try {
            $user = auth();
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $sessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT);
            $eventUuid = strtolower(trim((string)($_POST['event_uuid'] ?? $_POST['event_id'] ?? '')));
            $reason = trim((string)($_POST['reason'] ?? ''));
            $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') : strlen($reason);
            $remainingTime = filter_var($_POST['remaining_time'] ?? null, FILTER_VALIDATE_INT);
            if (
                !$sessionId
                || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $eventUuid)
                || $reasonLength > 255
                || $remainingTime === false
                || $remainingTime === null
                || $remainingTime < 0
            ) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Data sesi tidak valid']);
                return;
            }

            $pdo = db();
            $pdo->beginTransaction();
            $stmtSession = $pdo->prepare("
                SELECT es.id, es.exam_id, es.status, es.submitted_at, es.remaining_time, es.started_at, e.exam_pdf
                FROM exam_sessions es
                JOIN exams e ON e.id = es.exam_id
                WHERE es.id = ? AND es.user_id = ?
                FOR UPDATE
            ");
            $stmtSession->execute([$sessionId, $user['id']]);
            $session = $stmtSession->fetch();
            if (!$session) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sesi ujian tidak ditemukan']);
                return;
            }

            // Hitung waktu aktif yang sudah digunakan sejak started_at (server-side, bukan dari klien)
            $savedRemaining = (int)($session['remaining_time'] ?? 0);
            $startedAt    = $session['started_at'] ? strtotime($session['started_at']) : time();
            $now          = time();
            $elapsed      = max(0, $now - $startedAt);
            $newRemaining = max(0, $savedRemaining - $elapsed);

            $stmtEvent = $pdo->prepare("
                SELECT id
                FROM exam_violations
                WHERE session_id = ? AND event_uuid = ?
                LIMIT 1
            ");
            $stmtEvent->execute([$sessionId, $eventUuid]);
            $eventAlreadyLogged = $stmtEvent->fetchColumn() !== false;
            if (
                !$eventAlreadyLogged
                && ($session['submitted_at'] !== null
                    || in_array($session['status'], ['submitted', 'completed'], true))
            ) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Ujian sudah dikumpulkan']);
                return;
            }

            $eventReason = $reason !== '' ? $reason : 'Pelanggaran ujian';
            $eventResult = $this->persistViolationEvent(
                $pdo,
                (int)$sessionId,
                (int)$user['id'],
                $eventUuid,
                $eventReason
            );
            $violationCount = $eventResult['count'];
            $autoSubmitted = false;

            if (in_array($session['status'], ['active', 'in_progress'], true)) {
                if ($violationCount < self::VIOLATION_LIMIT) {
                    $pdo->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Violation recorded; suspension threshold not reached',
                        'violation_count' => $violationCount,
                        'auto_submitted' => false,
                    ]);
                    return;
                }

                $stmtSuspend = $pdo->prepare("
                    UPDATE exam_sessions
                    SET status = 'suspended',
                        suspended_at = NOW(),
                        suspension_reason = ?,
                        remaining_time = ?
                    WHERE id = ? AND submitted_at IS NULL
                ");
                $stmtSuspend->execute([$eventReason, $newRemaining, $sessionId]);

                $autoSubmitted = $this->autoSubmitViolationSession(
                    $pdo,
                    $session,
                    (int)$user['id'],
                    'Auto-submit: pelanggaran sampai batas',
                    true
                );
            } elseif ($session['status'] === 'suspended') {
                $autoSubmitted = $session['submitted_at'] !== null;
            } elseif (in_array($session['status'], ['submitted', 'completed'], true)) {
                $autoSubmitted = true;
            } else {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Status sesi tidak dapat ditangguhkan']);
                return;
            }

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => $eventResult['inserted'] ? 'Violation recorded' : 'Violation already recorded',
                'violation_count' => $violationCount,
                'auto_submitted' => $autoSubmitted,
            ]);
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Suspend session error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to suspend session']);
        }
    }

    public function addDuration($sessionId = null)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        if ($sessionId === null) {
            $sessionId = $_POST['session_id'] ?? null;
        }

        $additionalMinutes = (int)($_POST['additional_minutes'] ?? 0);

        if (!$sessionId || $additionalMinutes <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            return;
        }

        $user = auth();

        try {
            $pdo = db();
            $pdo->beginTransaction();

            // Verify session exists and get exam_id + owner
            $stmt = $pdo->prepare("
                SELECT es.id, es.exam_id, es.user_id, es.status, es.remaining_time,
                       e.title as exam_title
                FROM exam_sessions es
                JOIN exams e ON e.id = es.exam_id
                WHERE es.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();

            if (!$session) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sesi ujian tidak ditemukan']);
                return;
            }

            // Guru can only extend sessions for exams they created/own
            if ($user['role'] === 'guru') {
                $stmtOwn = $pdo->prepare("SELECT created_by FROM exams WHERE id = ?");
                $stmtOwn->execute([$session['exam_id']]);
                $examOwner = $stmtOwn->fetch();
                if (!$examOwner || (int)$examOwner['created_by'] !== (int)$user['id']) {
                    $pdo->rollBack();
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke ujian ini']);
                    return;
                }
            }

            // Only extend active or suspended sessions
            if (!in_array($session['status'], ['active', 'in_progress', 'suspended'], true)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Sesi tidak dapat diperpanjang']);
                return;
            }

            $addedSeconds = $additionalMinutes * 60;
            $newRemaining = (int)($session['remaining_time'] ?? 0) + $addedSeconds;

            $stmtUpdate = $pdo->prepare("
                UPDATE exam_sessions
                SET remaining_time = ?
                WHERE id = ? AND submitted_at IS NULL
            ");
            $stmtUpdate->execute([$newRemaining, $sessionId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Durasi berhasil ditambahkan ' . $additionalMinutes . ' menit',
                'session_id' => (int)$sessionId,
                'added_minutes' => $additionalMinutes,
                'new_remaining_time' => $newRemaining,
            ]);
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Add duration error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menambah durasi: ' . $e->getMessage()]);
        }
    }

    public function getRemainingTime($sessionId = null)
    {
        requireAuth();

        header('Content-Type: application/json');

        if ($sessionId === null) {
            $sessionId = $_GET['session_id'] ?? null;
        }

        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Session ID tidak valid']);
            return;
        }

        $user = auth();

        try {
            $stmt = db()->prepare("
                SELECT es.id, es.remaining_time, es.status, es.submitted_at
                FROM exam_sessions es
                WHERE es.id = ? AND es.user_id = ?
            ");
            $stmt->execute([$sessionId, $user['id']]);
            $session = $stmt->fetch();

            if (!$session) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sesi tidak ditemukan']);
                return;
            }

            echo json_encode([
                'success' => true,
                'session_id' => (int)$session['id'],
                'remaining_time' => (int)($session['remaining_time'] ?? 0),
                'status' => $session['status'],
                'submitted_at' => $session['submitted_at'],
            ]);
        } catch (Exception $e) {
            error_log("Get remaining time error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil waktu']);
        }
    }

    public function statusCheck()
    {
        requireAuth();
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $user = auth();

        $rawIds = $_GET['ids'] ?? $_POST['ids'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $rawIds)));

        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No exam IDs provided']);
            return;
        }

        $ids = array_slice($ids, 0, 50);

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // ── calon-siswa: can_access selalu 1 ────────────────────────────
            if ($user['role'] === 'calon-siswa') {
                $params = array_merge([$user['id']], $ids);
                $stmt = db()->prepare("
                    SELECT
                        e.id,
                        e.status,
                        e.start_time,
                        e.end_time,
                        CASE
                            WHEN NOW() < e.start_time THEN 'upcoming'
                            WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'active'
                            ELSE 'ended'
                        END AS exam_status,
                        1 AS can_access,
                        CASE
                            WHEN EXISTS (
                                SELECT 1 FROM exam_sessions es2
                                WHERE es2.exam_id = e.id AND es2.user_id = ?
                                AND (es2.status IN ('submitted','completed') OR es2.submitted_at IS NOT NULL)
                            ) THEN 1
                            ELSE 0
                        END AS already_submitted
                    FROM exams e
                    WHERE e.id IN ($placeholders)
                ");

                // ── siswa biasa: cek exam_participants ───────────────────────────
            } else {
                $params = array_merge([$user['id'], $user['id']], $ids);
                $stmt = db()->prepare("
                    SELECT
                        e.id,
                        e.status,
                        e.start_time,
                        e.end_time,
                        CASE
                            WHEN NOW() < e.start_time THEN 'upcoming'
                            WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'active'
                            ELSE 'ended'
                        END AS exam_status,
                        CASE
                            WHEN ep.exam_id IS NOT NULL THEN 1
                            WHEN NOT EXISTS (SELECT 1 FROM exam_participants WHERE exam_id = e.id) THEN 1
                            ELSE 0
                        END AS can_access,
                        CASE
                            WHEN EXISTS (
                                SELECT 1 FROM exam_sessions es2
                                WHERE es2.exam_id = e.id AND es2.user_id = ?
                                AND (es2.status IN ('submitted','completed') OR es2.submitted_at IS NOT NULL)
                            ) THEN 1
                            ELSE 0
                        END AS already_submitted
                    FROM exams e
                    LEFT JOIN exam_participants ep ON e.id = ep.exam_id AND ep.user_id = ?
                    WHERE e.id IN ($placeholders)
                ");
            }

            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $result[$row['id']] = [
                    'status'            => $row['status'],
                    'exam_status'       => $row['exam_status'],
                    'can_access'        => (bool)$row['can_access'],
                    'already_submitted' => (bool)$row['already_submitted'],
                    'start_time'        => $row['start_time'],
                    'end_time'          => $row['end_time'],
                ];
            }

            echo json_encode(['success' => true, 'exams' => $result]);
        } catch (\Exception $e) {
            error_log("statusCheck error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    private function isAnswerCorrect($type, $studentAnswer, $correctAnswer)
    {
        $student = trim($studentAnswer ?? '');
        $correct = trim($correctAnswer ?? '');

        if ($student === '' || $correct === '') return false;

        if ($type === 'short_answer') {
            if (is_numeric($student) && is_numeric($correct)) {
                return abs((float)$student - (float)$correct) < 0.0001;
            }
            return strtolower($student) === strtolower($correct);
        } elseif ($type === 'short_answer_text') {
            return strtolower($student) === strtolower($correct);
        } elseif ($type === 'essay') {
            return false;
        } elseif ($type === 'listening') {
            return strtolower($student) === strtolower($correct);
        } else {
            return strtolower($student) === strtolower($correct);
        }
    }

    private function getAnswerScore($type, $studentAnswer, $correctAnswer, $points, $optionScores = null)
    {
        if ($type === 'tkp') {
            $student = strtolower(trim($studentAnswer ?? ''));
            if ($student === '' || empty($optionScores)) return 0;
            $scores = is_array($optionScores) ? $optionScores : json_decode($optionScores, true);
            return (int)($scores[$student] ?? 0);
        }

        return $this->isAnswerCorrect($type, $studentAnswer, $correctAnswer) ? (int)$points : 0;
    }
}
