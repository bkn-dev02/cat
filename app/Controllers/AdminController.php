<?php

namespace App\Controllers;

class AdminController
{
    public function guruMapel()
    {
        requireAuth();
        requireRole(['admin']);

        $success = null;

        // Simpan assignment
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teacherId  = (int)($_POST['teacher_id'] ?? 0);
            $subjectId  = $_POST['subject_id'] !== '' ? (int)$_POST['subject_id'] : null;

            $stmt = db()->prepare("UPDATE users SET subject_id = ? WHERE id = ? AND role = 'guru'");
            $stmt->execute([$subjectId, $teacherId]);
            $success = 'Mata pelajaran berhasil disimpan.';
        }

        // Ambil semua guru beserta mapelnya
        $stmt = db()->query("
            SELECT u.id, u.username, u.full_name, u.subject_id, s.name as subject_name
            FROM users u
            LEFT JOIN subjects s ON u.subject_id = s.id
            WHERE u.role = 'guru'
            ORDER BY u.full_name ASC
        ");
        $teachers = $stmt->fetchAll();

        // Ambil semua subjects
        $stmt = db()->query("SELECT id, name FROM subjects ORDER BY name ASC");
        $subjects = $stmt->fetchAll();

        view('admin/guru-mapel', [
            'teachers' => $teachers,
            'subjects' => $subjects,
            'success'  => $success,
        ]);
    }

    public function resetSesi()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $message = '';
        $msgType = '';
        $results = [];

        // Proses reset sesi
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                $stmt = db()->prepare("
                    UPDATE users SET active_session_id = NULL
                    WHERE id = ? AND role IN ('siswa','student')
                ");
                $stmt->execute([$userId]);
                if ($stmt->rowCount() > 0) {
                    $message = 'Sesi berhasil direset. Siswa dapat login kembali.';
                    $msgType = 'success';
                } else {
                    $message = 'Akun tidak ditemukan.';
                    $msgType = 'error';
                }
            }
        }

        // Pencarian siswa
        $keyword = trim($_GET['q'] ?? '');
        if ($keyword !== '') {
            $stmt = db()->prepare("
                SELECT id, username, full_name, kelas, active_session_id, last_login_at
                FROM users
                WHERE role IN ('siswa','student')
                AND (username LIKE ? OR full_name LIKE ?)
                ORDER BY full_name ASC
                LIMIT 30
            ");
            $like = '%' . $keyword . '%';
            $stmt->execute([$like, $like]);
            $results = $stmt->fetchAll();
        }

        view('admin/reset-sesi', [
            'message' => $message,
            'msgType' => $msgType,
            'results' => $results,
            'keyword' => $keyword,
        ]);
    }

    public function cekSiswa()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $kelas = trim($_GET['kelas'] ?? '');
        $nama  = trim($_GET['nama'] ?? '');
        $filterDiisi = ($kelas !== '' || $nama !== '');
        $kelasList = db()->query("
            SELECT DISTINCT kelas FROM users
            WHERE role = 'siswa' AND kelas IS NOT NULL AND kelas != ''
            ORDER BY kelas ASC
        ")->fetchAll(\PDO::FETCH_COLUMN);

        $siswaList = [];
        if ($filterDiisi) {
            $sql = "SELECT id, username, full_name, kelas FROM users WHERE role = 'siswa'";
            $params = [];

            if ($kelas !== '') {
                $sql .= " AND kelas = ?";
                $params[] = $kelas;
            }
            if ($nama !== '') {
                $sql .= " AND (full_name LIKE ? OR username LIKE ?)";
                $like = '%' . $nama . '%';
                $params[] = $like;
                $params[] = $like;
            }
            $sql .= " ORDER BY full_name ASC";

            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $siswaList = $stmt->fetchAll();

            // Ambil nilai untuk seluruh siswa sekaligus, bukan satu query per siswa.
            $scoresByUser = [];
            if ($siswaList) {
                $userIds = array_column($siswaList, 'id');
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                try {
                    $nStmt = db()->prepare("
                        SELECT n.user_id, sub.name AS subject, n.nilai
                        FROM nilai n
                        JOIN subjects sub ON sub.id = n.subject_id
                        WHERE n.user_id IN ($placeholders)
                    ");
                    $nStmt->execute($userIds);
                    foreach ($nStmt->fetchAll() as $score) {
                        $scoresByUser[$score['user_id']][] = [
                            'subject' => $score['subject'],
                            'nilai' => $score['nilai'],
                        ];
                    }
                } catch (\Exception $e) {
                    error_log('Query nilai gagal: ' . $e->getMessage());
                }
            }

            foreach ($siswaList as &$s) {
                $s['nilai'] = $scoresByUser[$s['id']] ?? [];
            }
            unset($s);
        }

        view('admin/cek-siswa', [
            'siswaList'    => $siswaList,
            'kelasList'    => $kelasList,
            'kelas'        => $kelas,
            'nama'         => $nama,
            'filterDiisi'  => $filterDiisi,
        ]);
    }

    public function editSiswa()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $kelas    = trim($_POST['kelas'] ?? '');
            $backKelas = trim($_POST['back_kelas'] ?? '');
            $backNama  = trim($_POST['back_nama'] ?? '');

            $stmt = db()->prepare("
                UPDATE users SET full_name = ?, username = ?, kelas = ?
                WHERE id = ? AND role = 'siswa'
            ");
            $stmt->execute([$fullName, $username, $kelas, $id]);

            $query = http_build_query(array_filter([
                'kelas' => $backKelas,
                'nama'  => $backNama,
            ]));

            header('Location: ' . config('app_url') . '/admin/cekSiswa' . ($query ? '?' . $query : ''));
            exit;
        }

        $stmt = db()->prepare("SELECT id, username, full_name, kelas FROM users WHERE id = ? AND role = 'siswa'");
        $stmt->execute([$id]);
        $siswa = $stmt->fetch();

        if (!$siswa) {
            header('Location: ' . config('app_url') . '/admin/cekSiswa');
            exit;
        }

        view('admin/edit-siswa', [
            'siswa'     => $siswa,
            'backKelas' => $_GET['kelas'] ?? '',
            'backNama'  => $_GET['nama'] ?? '',
        ]);
    }

    public function suspendAkun()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $message = null;
        $user    = null;
        $results = [];
        $keyword = trim($_GET['q'] ?? '');
        $userId  = (int)($_GET['user_id'] ?? 0);

        if ($keyword !== '') {
            $stmt = db()->prepare("
                SELECT id, full_name, username, kelas
                FROM users
                WHERE role NOT IN ('admin', 'guru', 'yayasan', 'staff')
                AND (full_name LIKE ? OR username LIKE ?)
                ORDER BY full_name ASC
                LIMIT 30
            ");
            $like = '%' . $keyword . '%';
            $stmt->execute([$like, $like]);
            $results = $stmt->fetchAll();
        }

        if ($userId > 0) {
            $stmt = db()->prepare("SELECT id, full_name, username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch() ?: null;
        }

        // Dispatch aksi POST
        if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['reset_session_id'])) {
                $message = $this->handleReset($user);
            }
        }

        // Ambil sessions SETELAH aksi, supaya tampilan selalu data terbaru
        $sessions  = $user ? $this->fetchSessions($user['id']) : [];
        $serverNow = time();
        [$sessions, $timerData] = $this->prepareSessionView($sessions, $serverNow);

        if (isset($_POST['reset_session_id'])) {
            $message = $this->handleReset($user);
        } elseif (isset($_POST['retake_session_id'])) {
            $message = $this->handleRetake($user);
        }

        view('admin/suspend-akun', [
            'message'      => $message,
            'keyword'      => $keyword,
            'userId'       => $userId,
            'results'      => $results,
            'selectedUser' => $user,
            'sessions'     => $sessions,
            'timerData'    => $timerData,
            'serverNow'    => $serverNow,
        ]);
    }

    private const RETAKE_MAX_MINUTES = 60;

    private function handleRetake(array $user): array
    {
        $sid     = (int)($_POST['retake_session_id'] ?? 0);
        $minutes = filter_var($_POST['retake_duration_minutes'] ?? null, FILTER_VALIDATE_INT);

        if ($sid <= 0) {
            return $this->flashMessage('error', '❌ Session tidak valid.');
        }
        if ($minutes === false || $minutes === null || $minutes < 1 || $minutes > self::RETAKE_MAX_MINUTES) {
            return $this->flashMessage('error', '❌ Durasi harus berupa angka 1 sampai ' . self::RETAKE_MAX_MINUTES . ' menit.');
        }

        $pdo = db();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT id, exam_id, status, submitted_at, started_at
                FROM exam_sessions
                WHERE id = ? AND user_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$sid, $user['id']]);
            $s = $stmt->fetch();

            if (!$s) {
                $pdo->rollBack();
                return $this->flashMessage('error', '❌ Session tidak ditemukan.');
            }

            $stmtExam = $pdo->prepare("SELECT title, end_time FROM exams WHERE id = ?");
            $stmtExam->execute([$s['exam_id']]);
            $exam = $stmtExam->fetch() ?: [];

            if (!empty($exam['end_time']) && strtotime($exam['end_time']) <= time()) {
                $pdo->rollBack();
                return $this->flashMessage('error', '❌ Waktu ujian sudah berakhir. Retake tidak bisa diatur.');
            }

            $isWorkable = in_array($s['status'], ['active', 'in_progress'], true);
            $isFinished = !empty($s['submitted_at']) && in_array($s['status'], ['submitted', 'completed'], true);
            $isWaiting  = $isWorkable && empty($s['submitted_at']) && empty($s['started_at']);
            $isRunning  = $isWorkable && empty($s['submitted_at']) && !empty($s['started_at']);

            if ($isRunning) {
                $pdo->rollBack();
                return $this->flashMessage('error', '❌ Siswa sedang mengerjakan ujian ini. Durasi tidak dapat diubah.');
            }
            if (!$isFinished && !$isWaiting) {
                $pdo->rollBack();
                return $this->flashMessage('error', '❌ Session ini tidak dapat diatur ulang (status: ' . htmlspecialchars((string)$s['status']) . ').');
            }

            $pdo->prepare("
                UPDATE exam_sessions
                SET status            = 'active',
                    token_status      = 'active',
                    submitted_at      = NULL,
                    score             = NULL,
                    is_best_score     = 0,
                    suspended_at      = NULL,
                    suspension_reason = NULL,
                    started_at        = NULL,
                    remaining_time    = ?
                WHERE id = ? AND user_id = ?
            ")->execute([$minutes * 60, $sid, $user['id']]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('handleRetake failed: ' . $e->getMessage());
            return $this->flashMessage('error', '❌ Gagal mengatur retake. Tidak ada data yang diubah.');
        }

        $examTitle = htmlspecialchars($exam['title'] ?? '');
        $verb = $isWaiting ? 'Durasi ujian diubah' : 'Retake berhasil diatur';

        return $this->flashMessage(
            'success',
            "✅ {$verb} untuk <b>\"$examTitle\"</b>. Durasi: <b>{$minutes} menit</b>. "
                . "Waktu baru berjalan setelah siswa menekan Mulai di halaman Daftar Ujian. "
                . "Jawaban dan histori pelanggaran tetap tersimpan."
        );
    }

    private function handleReset(array $user): array
    {
        $sid = (int)($_POST['reset_session_id'] ?? 0);

        $stmt = db()->prepare("
            SELECT es.*, e.title, e.start_time, e.end_time
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.id = ? AND es.user_id = ?
        ");
        $stmt->execute([$sid, $user['id']]);
        $target = $stmt->fetch();

        if (!$target) {
            return $this->flashMessage('error', '❌ Session tidak ditemukan.');
        }
        if (!$this->isViolationSession($target)) {
            return $this->flashMessage('error', '❌ Session ini bukan auto-submit/suspend karena pelanggaran.');
        }

        $timeUntilEnd = max(0, strtotime($target['end_time']) - time());
        if ($timeUntilEnd <= 0) {
            return $this->flashMessage('error', '❌ Waktu ujian sudah berakhir. Tidak bisa dilanjutkan.');
        }

        $finalRemaining = $this->calcFinalRemaining($target);

        $stmt = db()->prepare("
            UPDATE exam_sessions
            SET status            = 'active',
                token_status      = 'active',
                submitted_at      = NULL,
                score             = NULL,
                is_best_score     = 0,
                violation_count   = (SELECT COUNT(*) FROM exam_violations WHERE session_id = ?),
                suspension_reason = NULL,
                suspended_at      = NULL,
                started_at        = NULL,
                remaining_time    = ?
            WHERE id = ? AND user_id = ? AND status = 'suspended'
        ");
        $stmt->execute([$sid, $finalRemaining, $sid, $user['id']]);

        if ($stmt->rowCount() === 0) {
            return $this->flashMessage('error', '❌ Session tidak dapat dilanjutkan karena status sudah berubah.');
        }

        $examTitle = htmlspecialchars($target['title'] ?? '');
        $menit     = floor($finalRemaining / 60);
        $detik     = $finalRemaining % 60;

        return $this->flashMessage(
            'success',
            "Session ujian <b>\"$examTitle\"</b> berhasil dilanjutkan. "
                . "Status diubah menjadi aktif, jawaban dan histori pelanggaran tetap tersimpan. "
                . "Sisa durasi: <b>{$menit} menit {$detik} detik</b>. "
                . "Siswa dapat melanjutkan ujian dari halaman Daftar Ujian."
        );
    }

    private function prepareSessionView(array $sessions, int $serverNow): array
    {
        $timerData = [];

        foreach ($sessions as &$s) {
            $canReset = $this->isViolationSession($s);
            $examEnd  = strtotime($s['end_time']);
            $savedRem = (int)$s['remaining_time'];
            $timeUntilEnd = max(0, $examEnd - $serverNow);

            $waitingForStudent = false;
            $runningLive = false;
            $effectiveRemaining = $savedRem;

            if (!$s['submitted_at'] && $s['status'] !== 'completed') {
                $startedAtTs = !empty($s['started_at']) ? strtotime($s['started_at']) : null;

                if ($canReset) {
                    // Sesi pelanggaran/suspended: waktu dibekukan
                    $effectiveRemaining = min($savedRem, $timeUntilEnd);
                    $referenceTime = null;
                    if (!empty($s['suspended_at'])) {
                        $referenceTime = strtotime($s['suspended_at']);
                    } elseif (!empty($s['submitted_at'])) {
                        $referenceTime = strtotime($s['submitted_at']);
                    }
                    $timerData[$s['id']] = [
                        'type'           => 'suspended',
                        'savedRemaining' => $savedRem,
                        'referenceTs'    => $referenceTime,
                        'endTs'          => $examEnd,
                        'serverNow'      => $serverNow,
                    ];
                } elseif (in_array($s['status'], ['active', 'in_progress'], true) && $startedAtTs === null) {
                    // Menunggu siswa menekan Mulai: waktu beku di durasi yang diinput admin.
                    $waitingForStudent = true;
                    $effectiveRemaining = $savedRem;
                    $timerData[$s['id']] = [
                        'type'           => 'waiting',
                        'savedRemaining' => $savedRem,
                        'serverNow'      => $serverNow,
                    ];
                } elseif (in_array($s['status'], ['active', 'in_progress'], true) && $startedAtTs !== null) {
                    // Siswa sedang mengerjakan: hitung mundur dari started_at server, tanpa endTs
                    $runningLive = true;
                    $elapsed = max(0, $serverNow - $startedAtTs);
                    $effectiveRemaining = max(0, $savedRem - $elapsed);
                    $timerData[$s['id']] = [
                        'type'           => 'running',
                        'savedRemaining' => $savedRem,
                        'startedAtTs'    => $startedAtTs,
                        'serverNow'      => $serverNow,
                    ];
                } elseif ($s['status'] === 'suspended') {
                    // Suspended non-violation (fallback): bekukan
                    $effectiveRemaining = min($savedRem, $timeUntilEnd);
                    $timerData[$s['id']] = [
                        'type'           => 'suspended',
                        'savedRemaining' => $savedRem,
                        'endTs'          => $examEnd,
                        'serverNow'      => $serverNow,
                    ];
                }
            }

            $resetRemaining = $this->calcFinalRemaining($s);
            $s['can_reset'] = $canReset;
            $s['already_reset'] = ($s['status'] === 'suspended' && empty($s['suspension_reason']) && empty($s['submitted_at']));
            $s['waiting_for_student'] = $waitingForStudent;
            $s['timer_running'] = $runningLive;
            $s['can_retake'] = (
                !empty($s['submitted_at'])
                && !$s['already_reset']
                && in_array($s['status'], ['submitted', 'completed'], true)
            );
            $s['status_label']        = $this->suspendStatusLabel($s, $canReset, $waitingForStudent);
            $s['effective_remaining'] = $effectiveRemaining;
            $s['remaining_minutes']   = floor($effectiveRemaining / 60);
            $s['remaining_seconds']   = $effectiveRemaining % 60;
            $s['exam_expired']        = $timeUntilEnd <= 0;
            $s['can_change_duration'] = $waitingForStudent && !$s['exam_expired'];
            $s['reset_minutes'] = floor(min($resetRemaining, $timeUntilEnd > 0 ? $timeUntilEnd : $resetRemaining) / 60);
            $s['reset_seconds'] = min($resetRemaining, $timeUntilEnd > 0 ? $timeUntilEnd : $resetRemaining) % 60;
        }
        unset($s);

        return [$sessions, $timerData];
    }

    private function flashMessage(string $type, string $text): array
    {
        return ['type' => $type, 'text' => $text];
    }
    private function fetchSessions(int $userId): array
    {
        $stmt = db()->prepare("
            SELECT
                es.id,
                e.id   AS exam_id,
                e.title,
                e.duration,
                e.start_time,
                e.end_time,
                es.status,
                es.started_at,
                es.submitted_at,
                es.remaining_time,
                es.violation_count,
                es.suspension_reason,
                es.suspended_at,
                (SELECT ev.reason FROM exam_violations ev WHERE ev.session_id = es.id ORDER BY ev.id DESC LIMIT 1) AS last_violation_reason
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.user_id = ?
            ORDER BY es.id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    private function isViolationSession(array $s): bool
    {
        if ($s['status'] === 'suspended') return true;

        if (
            !empty($s['suspension_reason']) &&
            (stripos($s['suspension_reason'], 'pelanggaran') !== false ||
                stripos($s['suspension_reason'], 'auto')        !== false ||
                stripos($s['suspension_reason'], 'suspend')     !== false ||
                stripos($s['suspension_reason'], 'violation')   !== false)
        ) {
            return true;
        }

        if ((int)$s['violation_count'] >= 1 && !empty($s['submitted_at']) && $s['status'] !== 'completed') {
            return true;
        }

        return false;
    }

    private function suspendStatusLabel(array $s, bool $isViolation, bool $waitingForStudent = false): array
    {
        // Sudah submit normal (siswa klik Kumpulkan Ujian)
        if (!empty($s['submitted_at']) && empty($s['already_reset'])) {
            return ['label' => '<i class="fas fa-check"></i> Submitted', 'class' => 'badge-submitted'];
        }

        // Sudah dilanjutkan admin, menunggu siswa mulai ujian lagi
        if ($waitingForStudent) {
            return ['label' => '<i class="fas fa-hourglass-half"></i> Siap Dilanjutkan', 'class' => 'badge-active'];
        }

        // Legacy state (suspended tanpa alasan setelah reset lama)
        if (!empty($s['already_reset'])) {
            return ['label' => '<i class="fas fa-play"></i> Sedang Aktif', 'class' => 'badge-active'];
        }

        return match ($s['status']) {
            'active', 'in_progress' => ['label' => '<i class="fas fa-circle"></i> Aktif', 'class' => 'badge-active'],
            'completed'             => ['label' => '<i class="fas fa-check"></i> Selesai',   'class' => 'badge-submitted'],
            'suspended'             => ['label' => '<i class="fas fa-pause"></i> Suspended', 'class' => 'badge-suspended'],
            default                 => ['label' => ucfirst($s['status'] ?? ''), 'class' => 'badge-other'],
        };
    }

    public function retake($sessionId = null)
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

        $durationMinutes = (int)($_POST['duration_minutes'] ?? 0);

        if (!$sessionId || $durationMinutes <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data tidak valid. Durasi harus lebih dari 0 menit.']);
            return;
        }

        $user = auth();

        try {
            $pdo = db();
            $pdo->beginTransaction();

            // Verifikasi session berdasarkan ID saja (admin/guru melakukan retake atas nama siswa)
            $stmt = $pdo->prepare("
                SELECT es.id, es.exam_id, es.user_id, es.status, es.submitted_at, es.violation_count,
                       e.title, e.start_time, e.end_time, e.duration, e.created_by
                FROM exam_sessions es
                JOIN exams e ON es.exam_id = e.id
                WHERE es.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$sessionId]);
            $target = $stmt->fetch();

            if (!$target) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Session ujian tidak ditemukan.']);
                return;
            }

            // Guru hanya bisa retake session untuk ujian yang mereka buat sendiri
            if ($user['role'] === 'guru') {
                if ((int)($target['created_by'] ?? 0) !== (int)$user['id']) {
                    $pdo->rollBack();
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke ujian ini.']);
                    return;
                }
            }

            // Hanya session yang sudah submit normal (tanpa pelanggaran saat submit)
            if (empty($target['submitted_at'])) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Session belum disubmit.']);
                return;
            }

            // Data pelanggaran adalah riwayat, tidak diblokir untuk retake.
            // Jika session adalah auto-submit karena pelanggaran, gunakan fitur "Lanjutkan Ujian" di halaman reset.
            if (!in_array($target['status'], ['submitted', 'completed'], true)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Status session tidak valid untuk retake.']);
                return;
            }

            // Validasi jendela akses ujian: retake hanya boleh dilakukan dalam rentang start_time s/d end_time
            $now = time();
            $examStart = strtotime($target['start_time']);
            $examEnd = strtotime($target['end_time']);

            if ($now < $examStart) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ujian belum dimulai. Tidak dapat di-retake.']);
                return;
            }

            if ($now > $examEnd) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Waktu ujian sudah berakhir. Tidak dapat di-retake.']);
                return;
            }

            // Update session: status active, remaining_time = durasi baru, tunggu siswa mulai (started_at NULL)
            $newRemaining = $durationMinutes * 60;

            $pdo->prepare("
                UPDATE exam_sessions
                SET status            = 'active',
                    token_status      = 'active',
                    submitted_at      = NULL,
                    score             = NULL,
                    is_best_score     = 0,
                    started_at        = NULL,
                    remaining_time    = ?
                WHERE id = ? AND user_id = ?
            ")->execute([$newRemaining, $sessionId, $target['user_id']]);

            $pdo->commit();

            $examTitle = htmlspecialchars($target['title'] ?? '');
            echo json_encode([
                'success' => true,
                'message' => "Retake berhasil diatur untuk <b>\"$examTitle\"</b>. "
                    . "Durasi retake: <b>{$durationMinutes} menit</b>. "
                    . "Sisa waktu akan mulai berjalan setelah siswa memulai ujian dari halaman Daftar Ujian.",
                'session_id' => (int)$sessionId,
                'duration_minutes' => $durationMinutes,
                'remaining_time' => $newRemaining,
            ]);
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Retake error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memproses retake: ' . $e->getMessage()]);
        }
    }

    private function calcFinalRemaining(array $s): int
    {
        $savedRemaining = (int)$s['remaining_time'];
        return max(60, $savedRemaining);
    }

    public function resetPassword()
    {
        requireAuth();
        requireRole(['admin']);

        $result  = null;
        $email   = trim($_POST['email'] ?? '');
        $newPass = $_POST['password'] ?? 'Bkn2026!';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = db()->prepare("SELECT id, full_name, kelas FROM users WHERE username = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $result = ['error' => "Email <b>" . htmlspecialchars($email) . "</b> tidak ditemukan di CAT."];
            } else {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user['id']]);
                $result = [
                    'success' => true,
                    'nama'    => $user['full_name'],
                    'email'   => $email,
                    'pass'    => $newPass,
                ];
            }
        }

        view('admin/reset-password', [
            'result'  => $result,
            'email'   => $email,
            'newPass' => $newPass,
        ]);
    }

    public function hapusSiswa()
    {
        requireAuth();
        requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $singleId = (int)($_POST['id'] ?? 0);
        if ($singleId > 0) {
            $ids[] = $singleId;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        $kelas = trim($_POST['kelas'] ?? '');
        $nama  = trim($_POST['nama'] ?? '');

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            try {
                db()->prepare("DELETE FROM nilai WHERE user_id IN ($placeholders)")->execute($ids);
            } catch (\Exception $e) {
                error_log('Gagal hapus nilai (mungkin tabel nilai belum ada): ' . $e->getMessage());
            }

            try {
                db()->prepare("DELETE FROM users WHERE id IN ($placeholders) AND role = 'siswa'")->execute($ids);
            } catch (\Exception $e) {
                error_log('Gagal hapus user: ' . $e->getMessage());
            }
        }

        $query = http_build_query(array_filter([
            'kelas' => $kelas,
            'nama'  => $nama,
        ]));

        header('Location: ' . config('app_url') . '/admin/cekSiswa' . ($query ? '?' . $query : ''));
        exit;
    }

    private const MAX_RESET_MINUTES = 30;

    public function resetSubmit()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $message  = null;
        $user     = null;
        $sessions = [];
        $email    = trim($_POST['email'] ?? $_GET['email'] ?? '');

        if ($email !== '') {
            $stmt = db()->prepare("
                SELECT id, full_name FROM users
                WHERE username = ? AND role NOT IN ('admin','guru','yayasan','staff')
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $sessions = $this->fetchSessions((int)$user['id']);
            }
        }

        // Proses reset
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_session_id']) && $user) {
            $sid = (int)$_POST['reset_session_id'];

            try {
                $stmt = db()->prepare("
                    SELECT es.*, e.title, e.start_time, e.end_time
                    FROM exam_sessions es
                    JOIN exams e ON es.exam_id = e.id
                    WHERE es.id = ? AND es.user_id = ?
                ");
                $stmt->execute([$sid, $user['id']]);
                $target = $stmt->fetch();

                if (!$target) {
                    $message = ['type' => 'error', 'text' => '❌ Session tidak ditemukan.'];
                } else {
                    $check = $this->canResetSession($target);

                    if (!$check['can']) {
                        $message = ['type' => 'error', 'text' => '❌ Tidak dapat direset: ' . htmlspecialchars($check['reason'])];
                    } else {
                        $restored = $this->calcRestoredRemaining($target);
                        $minsAgo  = (int)round($this->minutesSinceSubmit($target));

                        error_log(sprintf(
                            '[reset_submit] RESET: by=%s user=%s session=%d exam="%s" submitted_at=%s score=%s minsAgo=%d restored=%d',
                            $_SESSION['user']['username'] ?? '-',
                            $email,
                            $sid,
                            $target['title'],
                            $target['submitted_at'],
                            $target['score'],
                            $minsAgo,
                            $restored
                        ));

                        db()->prepare("
                            UPDATE exam_sessions
                            SET status = 'active', token_status = 'active',
                                submitted_at = NULL, score = NULL, is_best_score = 0,
                                remaining_time = ?
                            WHERE id = ? AND user_id = ?
                        ")->execute([$restored, $sid, $user['id']]);

                        $judul = htmlspecialchars($target['title'] ?? '');
                        $menit = floor($restored / 60);
                        $detik = $restored % 60;

                        $message = [
                            'type' => 'success',
                            'text' => "Ujian <b>\"$judul\"</b> berhasil direset! "
                                . "Submit {$minsAgo} menit lalu dibatalkan. "
                                . "Jawaban yang sudah diisi <b>tetap tersimpan</b>. "
                                . "Sisa waktu: <b>{$menit} menit {$detik} detik</b>. "
                                . "Minta siswa buka Daftar Ujian dan klik <b>\"Lanjutkan Ujian\"</b>.",
                        ];
                        $sessions = $this->fetchSessions((int)$user['id']);
                    }
                }
            } catch (\Throwable $e) {
                error_log('[reset_submit] POST error: ' . $e->getMessage() . ' | session=' . $sid);
                $message = ['type' => 'error', 'text' => 'Terjadi error. Cek error log server.'];
            }
        }

        // Siapkan data untuk view
        $serverNow = time();
        $timerData = [];
        foreach ($sessions as &$s) {
            $check          = $this->canResetSession($s);
            $s['can']       = $check['can'];
            $s['reason']    = $check['reason'];
            $s['violation'] = $this->isViolationSession($s);
            $s['mins_ago']  = !empty($s['submitted_at'])
                ? (int)round(($serverNow - strtotime($s['submitted_at'])) / 60) : null;
            $s['restored']  = $check['can'] ? $this->calcRestoredRemaining($s) : 0;
            $s['sl']        = $this->resetStatusLabel($s);

            if ($check['can']) {
                $timerData[$s['id']] = [
                    'submittedTs'    => strtotime($s['submitted_at']),
                    'savedRemaining' => (int)$s['remaining_time'],
                    'endTs'          => !empty($s['end_time']) ? strtotime($s['end_time']) : PHP_INT_MAX,
                    'serverNow'      => $serverNow,
                ];
            }
        }
        unset($s);

        view('admin/reset-submit', [
            'message'   => $message,
            'email'     => $email,
            'user'      => $user,
            'sessions'  => $sessions,
            'timerData' => $timerData,
            'maxMinutes' => self::MAX_RESET_MINUTES,
        ]);
    }

    private function minutesSinceSubmit(array $s): ?float
    {
        if (empty($s['submitted_at'])) return null;
        return (time() - strtotime($s['submitted_at'])) / 60;
    }

    private function canResetSession(array $s): array
    {
        if (empty($s['submitted_at'])) {
            return ['can' => false, 'reason' => 'Ujian belum disubmit.'];
        }
        if ($this->isViolationSession($s)) {
            return ['can' => false, 'reason' => 'Submit karena pelanggaran. Gunakan tool Reset Suspend.'];
        }
        if (!empty($s['end_time']) && strtotime($s['end_time']) < time()) {
            return ['can' => false, 'reason' => 'Waktu ujian sudah berakhir.'];
        }
        $mins = $this->minutesSinceSubmit($s);
        if ($mins !== null && $mins > self::MAX_RESET_MINUTES) {
            return ['can' => false, 'reason' => 'Sudah ' . round($mins) . ' menit sejak submit (batas: ' . self::MAX_RESET_MINUTES . ' menit).'];
        }
        return ['can' => true, 'reason' => ''];
    }

    private function calcRestoredRemaining(array $s): int
    {
        $saved   = (int)$s['remaining_time'];
        $now     = time();
        $elapsed = !empty($s['submitted_at']) ? max(0, $now - strtotime($s['submitted_at'])) : 0;
        $restored = max(0, $saved - $elapsed);

        if (!empty($s['end_time'])) {
            $restored = min($restored, max(0, strtotime($s['end_time']) - $now));
        }
        return max(60, $restored);
    }

    private function resetStatusLabel(array $s): array
    {
        if ($s['violation']) return ['label' => '🚫 Auto-Submit (Pelanggaran)', 'class' => 'rs-badge-suspended'];
        if (!empty($s['submitted_at'])) {
            return $s['can']
                ? ['label' => '📤 Submitted (Bisa Reset)', 'class' => 'rs-badge-reset']
                : ['label' => '✅ Submitted',              'class' => 'rs-badge-done'];
        }
        return match ($s['status']) {
            'active', 'in_progress' => ['label' => '🟢 Aktif',    'class' => 'rs-badge-active'],
            'completed'             => ['label' => '✅ Selesai',   'class' => 'rs-badge-done'],
            'suspended'             => ['label' => '🚫 Suspended', 'class' => 'rs-badge-suspended'],
            default                 => ['label' => ucfirst($s['status']), 'class' => 'rs-badge-other'],
        };
    }
}
