<?php

namespace App\Controllers;

class MonitorController
{
    private function monitorAccess(bool $json = false): array
    {
        $u = (isset($_SESSION['user']) && is_array($_SESSION['user'])) ? $_SESSION['user'] : [];
        $role = strtolower(trim((string)($_SESSION['role'] ?? $_SESSION['user_role'] ?? $u['role'] ?? '')));
        $userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? $u['id'] ?? 0);
        $allowed = ($role === 'admin') || ($role === 'guru' && $userId > 0);

        if (!$allowed) {
            error_log('Monitor access denied. role="' . $role . '", user_id=' . $userId
                . ', session keys: ' . implode(',', array_keys($_SESSION)));

            if ($json) {
                $this->monitorJsonError('Anda tidak memiliki akses', 403);
            }
            $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman monitor';
            redirect('dashboard');
            exit;
        }

        return [
            'role' => $role,
            'user_id' => $userId,
            'is_admin' => $role === 'admin',
        ];
    }

    private function canAccessExam(array $exam, array $access): bool
    {
        return $access['is_admin'] || (int)($exam['created_by'] ?? 0) === $access['user_id'];
    }

    private function monitorOwnerFilter(array $access): array
    {
        if ($access['is_admin']) {
            return ['', []];
        }
        return [' AND e.created_by = ?', [$access['user_id']]];
    }

    private function monitorJsonError(string $message, int $code): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    private function requireMonitorAccess(): array
    {
        requireAuth();
        requireRole(['admin', 'guru']);
        return $this->monitorAccess();
    }

    public function monitor($examId = null)
    {
        if ($examId) {
            $this->monitorDetail($examId);
            return;
        }

        $this->monitorList();
    }

    private function monitorDetail($examId): void
    {
        $access = $this->requireMonitorAccess();
        $stmt = db()->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();

        if (!$exam) {
            $_SESSION['error'] = 'Ujian tidak ditemukan';
            redirect('exam/monitor');
            return;
        }

        if (!$this->canAccessExam($exam, $access)) {
            $_SESSION['error'] = 'Anda tidak memiliki akses ke ujian ini';
            redirect('exam/monitor');
            return;
        }

        $status = strtolower(trim((string)($exam['status'] ?? '')));
        $statusLabel = $status !== '' ? ucfirst($status) : 'Tidak diketahui';
        $statusClass = match ($status) {
            'published' => 'status-active-exam',
            'draft' => 'status-draft-exam',
            default => 'status-ended-exam',
        };

        view('exams/monitor', [
            'exam' => $exam,
            'examStatusLabel' => $statusLabel,
            'examStatusClass' => $statusClass,
        ]);
    }

    private function monitorList(): void
    {
        $access = $this->requireMonitorAccess();

        // ===== pagination =====
        $perPage    = 24;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $totalExams = 0;
        $totalPages = 1;
        $exams      = [];

        [$ownerSql, $ownerParams] = $this->monitorOwnerFilter($access);

        try {
            $stmtCount = db()->prepare("
                SELECT COUNT(*)
                FROM exams e
                WHERE EXISTS (SELECT 1 FROM exam_sessions es WHERE es.exam_id = e.id)
                {$ownerSql}
            ");
            $stmtCount->execute($ownerParams);
            $totalExams = (int)$stmtCount->fetchColumn();

            $totalPages = max(1, (int)ceil($totalExams / $perPage));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * $perPage;

            $stmt = db()->prepare("
                SELECT e.id, e.title, e.start_time, e.duration
                FROM exams e
                WHERE EXISTS (
                    SELECT 1
                    FROM exam_sessions es
                    WHERE es.exam_id = e.id
                )
                {$ownerSql}
                ORDER BY e.start_time DESC, e.id DESC
                LIMIT {$perPage} OFFSET {$offset}
            ");
            $stmt->execute($ownerParams);
            $exams = $stmt->fetchAll();

            if ($exams) {
                $examIds = array_column($exams, 'id');
                $placeholders = implode(',', array_fill(0, count($examIds), '?'));

                $stmtSessions = db()->prepare("
                    SELECT
                        exam_id,
                        COUNT(DISTINCT CASE WHEN status = 'active' THEN user_id END) AS active_count,
                        COUNT(DISTINCT CASE WHEN status = 'submitted' THEN user_id END) AS submitted_count,
                        COUNT(DISTINCT CASE WHEN status = 'suspended' THEN user_id END) AS suspended_count,
                        COUNT(DISTINCT user_id) AS total_sessions
                    FROM exam_sessions
                    WHERE exam_id IN ({$placeholders})
                    GROUP BY exam_id
                ");
                $stmtSessions->execute($examIds);
                $sessionStats = [];
                foreach ($stmtSessions->fetchAll() as $row) {
                    $sessionStats[$row['exam_id']] = $row;
                }

                $stmtParticipants = db()->prepare("
                    SELECT p.exam_id, COUNT(DISTINCT p.user_id) AS not_started_count
                    FROM exam_participants p
                    LEFT JOIN exam_sessions es
                        ON es.exam_id = p.exam_id AND es.user_id = p.user_id
                    WHERE p.exam_id IN ({$placeholders})
                      AND es.id IS NULL
                    GROUP BY p.exam_id
                ");
                $stmtParticipants->execute($examIds);
                $notStartedByExam = [];
                foreach ($stmtParticipants->fetchAll() as $row) {
                    $notStartedByExam[$row['exam_id']] = (int)$row['not_started_count'];
                }

                foreach ($exams as &$exam) {
                    $stats = $sessionStats[$exam['id']] ?? [];
                    $exam['active_count'] = (int)($stats['active_count'] ?? 0);
                    $exam['submitted_count'] = (int)($stats['submitted_count'] ?? 0);
                    $exam['suspended_count'] = (int)($stats['suspended_count'] ?? 0);
                    $exam['total_sessions'] = (int)($stats['total_sessions'] ?? 0);
                    $exam['not_started_count'] = $notStartedByExam[$exam['id']] ?? 0;
                    $exam['total_participants'] = $exam['total_sessions'] + $exam['not_started_count'];
                }
                unset($exam);
            }
        } catch (\Throwable $e) {
            error_log('Monitor list error: ' . $e->getMessage());
            $exams = [];
        }

        foreach ($exams as &$exam) {
            $exam['id'] = (int)$exam['id'];
            $exam['duration'] = (int)$exam['duration'];
            $exam['active_count'] = (int)$exam['active_count'];
            $exam['submitted_count'] = (int)$exam['submitted_count'];
            $exam['not_started_count'] = (int)$exam['not_started_count'];
            $exam['total_count'] = (int)($exam['total_participants'] ?? $exam['total_sessions'] ?? 0);
            $exam['formatted_start_time'] = formatWaktuIndonesia($exam['start_time'], 'lengkap');
        }
        unset($exam);

        $pages = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) {
                $pages[] = $i;
            } elseif (end($pages) !== '...') {
                $pages[] = '...';
            }
        }

        $appUrl = config('app_url');

        view('exams/monitor_list', [
            'exams'             => $exams,
            'page'              => $page,
            'totalExams'        => $totalExams,
            'totalPages'        => $totalPages,
            'pages'             => $pages,
            'baseUrl'           => $appUrl . '/exam/monitor',
            'appUrl'            => $appUrl,
            'bootstrapCssUrl'   => asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css'),
            'fontAwesomeCssUrl' => asset_url('vendor/fontawesome/6.0.0/css/all.min.css'),
            'bootstrapJsUrl'    => asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js'),
            'hasExams'          => !empty($exams),
            'showPagination'    => $totalPages > 1,
            'hasPreviousPage'   => $page > 1,
            'previousPage'      => $page - 1,
            'hasNextPage'       => $page < $totalPages,
            'nextPage'          => $page + 1,
            'from'              => $totalExams > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to'                => min($page * $perPage, $totalExams),
            'lastCheck'         => date('H:i:s'),
        ]);
    }

    // method monitorData
    public function monitorData($examId = null)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

        try {
            requireAuth();
            requireRole(['admin', 'guru']);
        } catch (\Throwable $e) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage()
            ]);
            exit;
        }

        $access = $this->monitorAccess(true);

        $statusFilter = $_GET['status'] ?? 'all';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, (int)($_GET['limit'] ?? 50));
        $offset = ($page - 1) * $limit;

        try {
            if ($examId) {
                $stmtOwn = db()->prepare("SELECT id, created_by FROM exams WHERE id = ?");
                $stmtOwn->execute([$examId]);
                $examRow = $stmtOwn->fetch();

                if (!$examRow) {
                    $this->monitorJsonError('Ujian tidak ditemukan', 404);
                }
                if (!$this->canAccessExam($examRow, $access)) {
                    $this->monitorJsonError('Anda tidak memiliki akses ke ujian ini', 403);
                }

                // Ambil duration dan total_questions sekali
                $stmtExam = db()->prepare("SELECT duration, id FROM exams WHERE id = ?");
                $stmtExam->execute([$examId]);
                $examInfo = $stmtExam->fetch();
                $durationSeconds = (int)($examInfo['duration'] ?? 0) * 60;
                $totalQuestions = (int)($examInfo['id'] ? 0 : 0);
                $stmtTq = db()->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = ?");
                $stmtTq->execute([$examId]);
                $totalQuestions = (int)($stmtTq->fetchColumn() ?? 0);

                // Fungsi bantu untuk memproses row session menjadi array standar
                $processRow = function ($row) use ($durationSeconds, $totalQuestions) {
                    $remainingTimeDb = (int)($row['remaining_time'] ?? 0);
                    $elapsedSeconds = max(0, (int)($row['elapsed_seconds'] ?? 0));
                    if ($remainingTimeDb > 0) {
                        $remainingSeconds = $remainingTimeDb;
                    } else {
                        $remainingSeconds = max(0, $durationSeconds - $elapsedSeconds);
                    }
                    return [
                        'id' => $row['id'],
                        'exam_id' => $row['exam_id'],
                        'user_id' => $row['user_id'],
                        'status' => $row['status'],
                        'started_at' => $row['started_at'],
                        'submitted_at' => $row['submitted_at'],
                        'exam_title' => $row['exam_title'],
                        'duration' => $row['duration'],
                        'student_name' => $row['full_name'],
                        'full_name' => $row['full_name'],
                        'username' => $row['username'],
                        'kelas' => $row['kelas'],
                        'elapsed_seconds' => $elapsedSeconds,
                        'remaining_seconds' => $remainingSeconds,
                        'violation_count' => (int)($row['violation_count'] ?? 0),
                        'suspension_reason' => $row['suspension_reason'] ?? null,
                        'suspended_at' => $row['suspended_at'] ?? null,
                        'answered_count' => (int)$row['answered_count'],
                        'total_questions' => $totalQuestions
                    ];
                };

                $processedSessions = [];

                // ============================================================
                // 1. Tentukan kelas yang di-assign ke ujian ini
                // ============================================================
                $assignedKelas = [];
                $stmtKelas = db()->prepare("
                    SELECT DISTINCT u.kelas
                    FROM exam_participants ep
                    JOIN users u ON ep.user_id = u.id
                    WHERE ep.exam_id = ? AND u.kelas IS NOT NULL AND u.kelas != ''
                ");
                $stmtKelas->execute([$examId]);
                while ($r = $stmtKelas->fetch()) {
                    $assignedKelas[] = $r['kelas'];
                }
                $hasAssignedKelas = !empty($assignedKelas);
                $placeholders = $hasAssignedKelas ? implode(',', array_fill(0, count($assignedKelas), '?')) : '';

                // ============================================================
                // 2. Hitung SEMUA summary counts (untuk statistik dan badge)
                //    Tidak dipengaruhi oleh status filter atau pagination
                // ============================================================
                if ($hasAssignedKelas) {
                    $kelasCondition = "AND u.kelas IN ($placeholders)";
                } else {
                    $kelasCondition = "";
                }

                // Total peserta yang di-assign (termasuk yang belum mulai)
                $stmtTotal = db()->prepare("
                    SELECT COUNT(DISTINCT u.id) as cnt
                    FROM users u
                    WHERE u.role = 'siswa' $kelasCondition
                ");
                $stmtTotal->execute($hasAssignedKelas ? $assignedKelas : []);
                $totalParticipants = (int)($stmtTotal->fetchColumn() ?? 0);

                // Aktif (di exam_sessions dengan status active/in_progress)
                $stmtActive = db()->prepare("
                    SELECT COUNT(DISTINCT es.user_id) as cnt
                    FROM exam_sessions es
                    JOIN users u ON es.user_id = u.id
                    WHERE es.exam_id = ? AND es.status IN ('active', 'in_progress') $kelasCondition
                ");
                $stmtActive->execute($hasAssignedKelas ? array_merge([$examId], $assignedKelas) : [$examId]);
                $activeCount = (int)($stmtActive->fetchColumn() ?? 0);

                // Selesai (di exam_sessions dengan status submitted/completed/graded)
                $stmtSubmitted = db()->prepare("
                    SELECT COUNT(DISTINCT es.user_id) as cnt
                    FROM exam_sessions es
                    JOIN users u ON es.user_id = u.id
                    WHERE es.exam_id = ? AND es.status IN ('submitted', 'completed', 'graded') $kelasCondition
                ");
                $stmtSubmitted->execute($hasAssignedKelas ? array_merge([$examId], $assignedKelas) : [$examId]);
                $submittedCount = (int)($stmtSubmitted->fetchColumn() ?? 0);

                // Suspend
                $stmtSuspended = db()->prepare("
                    SELECT COUNT(DISTINCT es.user_id) as cnt
                    FROM exam_sessions es
                    JOIN users u ON es.user_id = u.id
                    WHERE es.exam_id = ? AND es.status = 'suspended' $kelasCondition
                ");
                $stmtSuspended->execute($hasAssignedKelas ? array_merge([$examId], $assignedKelas) : [$examId]);
                $suspendedCount = (int)($stmtSuspended->fetchColumn() ?? 0);

                $notStartedCount = max(0, $totalParticipants - $activeCount - $submittedCount - $suspendedCount);

                $summaryCounts = [
                    'total_participants' => $totalParticipants,
                    'active_count' => $activeCount,
                    'submitted_count' => $submittedCount,
                    'suspended_count' => $suspendedCount,
                    'not_started_count' => $notStartedCount,
                ];

                // ============================================================
                // 3. Ambil data dengan LIMIT/OFFSET hanya untuk tampilan
                //    Filter menyesuaikan statusFilter
                // ============================================================
                $processedSessions = [];

                if ($statusFilter === 'all') {
                    if ($hasAssignedKelas) {
                        $stmtUsers = db()->prepare("
                            SELECT u.id, u.username, u.full_name, u.kelas,
                                   es.id as session_id, es.exam_id as session_exam_id, es.status as session_status,
                                   es.started_at, es.submitted_at, es.remaining_time,
                                   COALESCE(es.violation_count, 0) as violation_count,
                                   es.suspension_reason, es.suspended_at,
                                   TIMESTAMPDIFF(SECOND, es.started_at, NOW()) as elapsed_seconds,
                                   e.title as exam_title, e.duration,
                                   COALESCE(ac.answered_count, 0) as answered_count
                            FROM users u
                            CROSS JOIN exams e
                            LEFT JOIN exam_sessions es ON es.user_id = u.id AND es.exam_id = e.id
                            LEFT JOIN (
                                SELECT a.session_id, COUNT(*) AS answered_count
                                FROM answers a
                                WHERE a.session_id IN (SELECT id FROM exam_sessions WHERE exam_id = ?)
                                GROUP BY a.session_id
                            ) ac ON ac.session_id = es.id
                            WHERE u.role = 'siswa' AND e.id = ? AND u.kelas IN ($placeholders)
                            ORDER BY u.full_name
                            LIMIT ? OFFSET ?
                        ");
                        $stmtUsers->execute(array_merge([$examId, $examId], $assignedKelas, [$limit, $offset]));
                    } else {
                        $stmtUsers = db()->prepare("
                            SELECT u.id, u.username, u.full_name, u.kelas,
                                   es.id as session_id, es.exam_id as session_exam_id, es.status as session_status,
                                   es.started_at, es.submitted_at, es.remaining_time,
                                   COALESCE(es.violation_count, 0) as violation_count,
                                   es.suspension_reason, es.suspended_at,
                                   TIMESTAMPDIFF(SECOND, es.started_at, NOW()) as elapsed_seconds,
                                   e.title as exam_title, e.duration,
                                   COALESCE(ac.answered_count, 0) as answered_count
                            FROM users u
                            CROSS JOIN exams e
                            LEFT JOIN exam_sessions es ON es.user_id = u.id AND es.exam_id = e.id
                            LEFT JOIN (
                                SELECT a.session_id, COUNT(*) AS answered_count
                                FROM answers a
                                WHERE a.session_id IN (SELECT id FROM exam_sessions WHERE exam_id = ?)
                                GROUP BY a.session_id
                            ) ac ON ac.session_id = es.id
                            WHERE u.role = 'siswa' AND e.id = ?
                            ORDER BY u.full_name
                            LIMIT ? OFFSET ?
                        ");
                        $stmtUsers->execute(array_merge([$examId, $examId], [$limit, $offset]));
                    }
                    $allUsers = $stmtUsers->fetchAll();

                    foreach ($allUsers as $row) {
                        $sessionStatus = $row['session_status'] ?? null;
                        if ($sessionStatus === null) {
                            $sessionStatus = 'pending';
                        }

                        $processedSessions[] = $processRow([
                            'id' => $row['session_id'],
                            'exam_id' => $row['session_exam_id'],
                            'user_id' => $row['id'],
                            'status' => $sessionStatus,
                            'started_at' => $row['started_at'],
                            'submitted_at' => $row['submitted_at'],
                            'exam_title' => $row['exam_title'],
                            'duration' => $row['duration'],
                            'full_name' => $row['full_name'],
                            'username' => $row['username'],
                            'kelas' => $row['kelas'],
                            'remaining_time' => $row['remaining_time'],
                            'elapsed_seconds' => $row['elapsed_seconds'],
                            'violation_count' => $row['violation_count'],
                            'suspension_reason' => $row['suspension_reason'],
                            'suspended_at' => $row['suspended_at'],
                            'answered_count' => $row['answered_count']
                        ]);
                    }
                } elseif ($statusFilter === 'pending') {
                    if ($hasAssignedKelas) {
                        $stmtUsers = db()->prepare("
                            SELECT u.id, u.username, u.full_name, u.kelas
                            FROM users u
                            WHERE u.role = 'siswa'
                              AND u.kelas IN ($placeholders)
                              AND NOT EXISTS (
                                  SELECT 1 FROM exam_sessions es
                                  WHERE es.exam_id = ? AND es.user_id = u.id
                              )
                            ORDER BY u.full_name
                            LIMIT ? OFFSET ?
                        ");
                        $stmtUsers->execute(array_merge($assignedKelas, [$examId], [$limit, $offset]));
                    } else {
                        $stmtUsers = db()->prepare("
                            SELECT u.id, u.username, u.full_name, u.kelas
                            FROM users u
                            WHERE u.role = 'siswa'
                              AND NOT EXISTS (
                                  SELECT 1 FROM exam_sessions es
                                  WHERE es.exam_id = ? AND es.user_id = u.id
                              )
                            ORDER BY u.full_name
                            LIMIT ? OFFSET ?
                        ");
                        $stmtUsers->execute(array_merge([$examId], [$limit, $offset]));
                    }
                    $allUsers = $stmtUsers->fetchAll();

                    foreach ($allUsers as $row) {
                        $processedSessions[] = [
                            'id' => 'pending_' . $row['id'],
                            'exam_id' => $examId,
                            'user_id' => $row['id'],
                            'status' => 'pending',
                            'started_at' => null,
                            'submitted_at' => null,
                            'exam_title' => $examRow['title'] ?? '',
                            'duration' => $examInfo['duration'] ?? 0,
                            'student_name' => $row['full_name'],
                            'full_name' => $row['full_name'],
                            'username' => $row['username'],
                            'kelas' => $row['kelas'],
                            'elapsed_seconds' => 0,
                            'remaining_seconds' => $durationSeconds,
                            'violation_count' => 0,
                            'suspension_reason' => null,
                            'suspended_at' => null,
                            'answered_count' => 0,
                            'total_questions' => $totalQuestions
                        ];
                    }
                } else {
                    $statusMap = [
                        'active' => "es.status IN ('active', 'in_progress')",
                        'submitted' => "es.status IN ('submitted', 'completed', 'graded')",
                        'suspended' => "es.status = 'suspended'"
                    ];
                    $whereStatus = $statusMap[$statusFilter] ?? "1=1";

                    if ($hasAssignedKelas) {
                        $stmt = db()->prepare("
                            SELECT
                                es.id,
                                es.exam_id,
                                es.user_id,
                                es.status,
                                es.started_at,
                                es.submitted_at,
                                es.remaining_time,
                                COALESCE(es.violation_count, 0) as violation_count,
                                es.suspension_reason,
                                es.suspended_at,
                                TIMESTAMPDIFF(SECOND, es.started_at, NOW()) as elapsed_seconds,
                                e.title as exam_title,
                                e.duration,
                                u.username,
                                COALESCE(u.full_name, u.username) as full_name,
                                u.kelas,
                                COALESCE(ac.answered_count, 0) as answered_count
                            FROM exam_sessions es
                            JOIN users u ON es.user_id = u.id
                            JOIN exams e ON es.exam_id = e.id
                            LEFT JOIN (
                                SELECT a.session_id, COUNT(*) AS answered_count
                                FROM answers a
                                WHERE a.session_id IN (SELECT id FROM exam_sessions WHERE exam_id = ?)
                                GROUP BY a.session_id
                            ) ac ON ac.session_id = es.id
                            WHERE es.exam_id = ? AND $whereStatus AND u.kelas IN ($placeholders)
                            ORDER BY es.started_at DESC
                            LIMIT ? OFFSET ?
                        ");
                        $stmt->execute(array_merge([$examId, $examId], $assignedKelas, [$limit, $offset]));
                    } else {
                        $stmt = db()->prepare("
                            SELECT
                                es.id,
                                es.exam_id,
                                es.user_id,
                                es.status,
                                es.started_at,
                                es.submitted_at,
                                es.remaining_time,
                                COALESCE(es.violation_count, 0) as violation_count,
                                es.suspension_reason,
                                es.suspended_at,
                                TIMESTAMPDIFF(SECOND, es.started_at, NOW()) as elapsed_seconds,
                                e.title as exam_title,
                                e.duration,
                                u.username,
                                COALESCE(u.full_name, u.username) as full_name,
                                u.kelas,
                                COALESCE(ac.answered_count, 0) as answered_count
                            FROM exam_sessions es
                            JOIN users u ON es.user_id = u.id
                            JOIN exams e ON es.exam_id = e.id
                            LEFT JOIN (
                                SELECT a.session_id, COUNT(*) AS answered_count
                                FROM answers a
                                WHERE a.session_id IN (SELECT id FROM exam_sessions WHERE exam_id = ?)
                                GROUP BY a.session_id
                            ) ac ON ac.session_id = es.id
                            WHERE es.exam_id = ? AND $whereStatus
                            ORDER BY es.started_at DESC
                            LIMIT ? OFFSET ?
                        ");
                        $stmt->execute(array_merge([$examId, $examId], [$limit, $offset]));
                    }
                    $sessions = $stmt->fetchAll();

                    foreach ($sessions as $session) {
                        $processedSessions[] = $processRow($session);
                    }
                }

                // Total data yang cocok dengan status filter (untuk pagination)
                if ($statusFilter === 'all') {
                    $totalCount = $totalParticipants;
                } elseif ($statusFilter === 'pending') {
                    $stmtPendingTotal = db()->prepare("
                        SELECT COUNT(*) as cnt
                        FROM users u
                        WHERE u.role = 'siswa' $kelasCondition
                          AND NOT EXISTS (
                              SELECT 1 FROM exam_sessions es
                              WHERE es.exam_id = ? AND es.user_id = u.id
                          )
                    ");
                    $stmtPendingTotal->execute($hasAssignedKelas ? array_merge($assignedKelas, [$examId]) : [$examId]);
                    $totalCount = (int)($stmtPendingTotal->fetchColumn() ?? 0);
                } else {
                    $statusMap2 = [
                        'active' => "es.status IN ('active', 'in_progress')",
                        'submitted' => "es.status IN ('submitted', 'completed', 'graded')",
                        'suspended' => "es.status = 'suspended'"
                    ];
                    $whereStatus2 = $statusMap2[$statusFilter] ?? "1=1";
                    $stmtDataTotal = db()->prepare("
                        SELECT COUNT(*) as cnt
                        FROM exam_sessions es
                        JOIN users u ON es.user_id = u.id
                        WHERE es.exam_id = ? AND $whereStatus2 $kelasCondition
                    ");
                    $stmtDataTotal->execute($hasAssignedKelas ? array_merge([$examId], $assignedKelas) : [$examId]);
                    $totalCount = (int)($stmtDataTotal->fetchColumn() ?? 0);
                }

                $summary = [
                    'total_participants' => $summaryCounts['total_participants'],
                    'active_count' => $summaryCounts['active_count'],
                    'submitted_count' => $summaryCounts['submitted_count'],
                    'suspended_count' => $summaryCounts['suspended_count'],
                    'not_started_count' => $summaryCounts['not_started_count'],
                ];

                $lastPage = max(1, ceil($totalCount / $limit));

                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $processedSessions,
                    'summary' => $summary,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $totalCount,
                        'last_page' => $lastPage,
                        'from' => $totalCount > 0 ? (($page - 1) * $limit + 1) : 0,
                        'to' => min($page * $limit, $totalCount)
                    ],
                    'debug' => [
                        'exam_id' => $examId,
                        'status' => $statusFilter,
                        'session_count' => count($processedSessions),
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ]);
                exit;
            } else {
                [$ownerSql, $ownerParams] = $this->monitorOwnerFilter($access);

                $stmt = db()->prepare("
                    SELECT
                        es.id,
                        es.exam_id,
                        es.user_id,
                        es.status,
                        es.started_at,
                        e.title as exam_title,
                        u.username,
                        u.full_name
                    FROM exam_sessions es
                    JOIN users u ON es.user_id = u.id
                    JOIN exams e ON es.exam_id = e.id
                    WHERE es.started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    {$ownerSql}
                    ORDER BY es.started_at DESC
                ");
                $stmt->execute($ownerParams);
                $sessions = $stmt->fetchAll();

                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'data' => ['Semua Peserta' => $sessions]
                ]);
                exit;
            }
        } catch (\Throwable $e) {
            error_log("MonitorData Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'debug' => [
                    'exam_id' => $examId,
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            exit;
        }
    }

    public function monitorViolations($sessionId = null): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');

        try {
            requireAuth();
            requireRole(['admin', 'guru']);
        } catch (\Throwable $e) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage(),
            ]);
            exit;
        }

        $access = $this->monitorAccess(true);
        $sessionId = (int)$sessionId;
        if ($sessionId <= 0) {
            $this->monitorJsonError('Session tidak valid', 400);
        }

        try {
            $stmtSession = db()->prepare("
                SELECT es.id, e.created_by
                FROM exam_sessions es
                JOIN exams e ON e.id = es.exam_id
                WHERE es.id = ?
            ");
            $stmtSession->execute([$sessionId]);
            $session = $stmtSession->fetch();

            if (!$session) {
                $this->monitorJsonError('Session tidak ditemukan', 404);
            }
            if (!$this->canAccessExam($session, $access)) {
                $this->monitorJsonError('Anda tidak memiliki akses ke session ini', 403);
            }

            $stmtViolations = db()->prepare("
                SELECT reason, violation_count, created_at
                FROM exam_violations
                WHERE session_id = ?
                ORDER BY created_at ASC
            ");
            $stmtViolations->execute([$sessionId]);

            $violations = [];
            foreach ($stmtViolations->fetchAll() as $row) {
                $violations[] = [
                    'reason' => $row['reason'],
                    'count' => $row['violation_count'],
                    'time' => $row['created_at'],
                ];
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $violations]);
            exit;
        } catch (\Throwable $e) {
            error_log('Monitor violations error: ' . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ]);
            exit;
        }
    }
}
