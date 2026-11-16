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
                        COUNT(DISTINCT CASE WHEN status IN ('active', 'in_progress')
                                            THEN user_id END) AS active_count,
                        COUNT(DISTINCT CASE WHEN status IN ('completed', 'submitted', 'graded')
                                            THEN user_id END) AS submitted_count,
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
                        COALESCE(answer_counts.answered_count, 0) as answered_count
                    FROM exam_sessions es
                    JOIN users u ON es.user_id = u.id
                    JOIN exams e ON es.exam_id = e.id
                    LEFT JOIN (
                        SELECT a.session_id, COUNT(*) AS answered_count
                        FROM exam_sessions answer_sessions
                        JOIN answers a ON a.session_id = answer_sessions.id
                        WHERE answer_sessions.exam_id = ?
                        GROUP BY a.session_id
                    ) answer_counts ON answer_counts.session_id = es.id
                    WHERE es.exam_id = ?
                    ORDER BY es.started_at DESC
                ");
                $stmt->execute([$examId, $examId]);
                $sessions = $stmt->fetchAll();

                $stmtSummary = db()->prepare("
                    SELECT
                        COUNT(DISTINCT es.user_id) AS total_sessions,
                        COUNT(DISTINCT CASE WHEN es.status IN ('active','in_progress') THEN es.user_id END) AS active_count,
                        COUNT(DISTINCT CASE WHEN es.status IN ('completed','submitted','graded') THEN es.user_id END) AS submitted_count,
                        COUNT(DISTINCT CASE WHEN es.status = 'suspended' THEN es.user_id END) AS suspended_count,
                        (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = ?) AS total_questions,
                        (
                            SELECT COUNT(DISTINCT p.user_id)
                            FROM exam_participants p
                            WHERE p.exam_id = ?
                              AND NOT EXISTS (
                                  SELECT 1
                                  FROM exam_sessions s
                                  WHERE s.exam_id = p.exam_id AND s.user_id = p.user_id
                              )
                        ) AS not_started_count
                    FROM exam_sessions es
                    WHERE es.exam_id = ?
                ");
                $stmtSummary->execute([$examId, $examId, $examId]);
                $summaryRow = $stmtSummary->fetch();

                $totalSessions = (int)($summaryRow['total_sessions'] ?? 0);
                $notStarted    = (int)($summaryRow['not_started_count'] ?? 0);

                $summary = [
                    'total_participants' => $totalSessions + $notStarted,
                    'active_count'       => (int)($summaryRow['active_count'] ?? 0),
                    'submitted_count'    => (int)($summaryRow['submitted_count'] ?? 0),
                    'suspended_count'    => (int)($summaryRow['suspended_count'] ?? 0),
                    'not_started_count'  => $notStarted,
                ];

                $processedSessions = [];
                foreach ($sessions as $session) {
                    $durationSeconds = (int)($session['duration'] ?? 0) * 60;
                    $remainingTimeDb = (int)($session['remaining_time'] ?? 0);
                    $elapsedSeconds = max(0, (int)($session['elapsed_seconds'] ?? 0));

                    // Gunakan remaining_time dari DB (disimpan siswa setiap 30 detik) sebagai sumber kebenaran.
                    // Fallback ke perhitungan dari started_at jika remaining_time belum tersedia (0 atau NULL).
                    if ($remainingTimeDb > 0) {
                        $remainingSeconds = $remainingTimeDb;
                    } else {
                        $remainingSeconds = max(0, $durationSeconds - $elapsedSeconds);
                    }

                    $processedSessions[] = [
                        'id' => $session['id'],
                        'exam_id' => $session['exam_id'],
                        'user_id' => $session['user_id'],
                        'status' => $session['status'],
                        'started_at' => $session['started_at'],
                        'submitted_at' => $session['submitted_at'],
                        'exam_title' => $session['exam_title'],
                        'duration' => $session['duration'],
                        'student_name' => $session['full_name'],
                        'full_name' => $session['full_name'],
                        'username' => $session['username'],
                        'kelas' => $session['kelas'],
                        'elapsed_seconds' => $elapsedSeconds,
                        'remaining_seconds' => $remainingSeconds,
                        'violation_count' => (int)($session['violation_count'] ?? 0),
                        'suspension_reason' => $session['suspension_reason'] ?? null,
                        'suspended_at' => $session['suspended_at'] ?? null,
                        'answered_count' => (int)$session['answered_count'],
                        'total_questions' => (int)($summaryRow['total_questions'] ?? 0)
                    ];
                }

                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $processedSessions,
                    'summary' => $summary,
                    'debug' => [
                        'exam_id' => $examId,
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
