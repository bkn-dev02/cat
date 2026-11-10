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
    
        // Cek apakah filter di-submit (ada query string sama sekali)
        $filterDiisi = ($kelas !== '' || $nama !== '');
    
        $kelasList = db()->query("
            SELECT DISTINCT kelas FROM users
            WHERE role = 'siswa' AND kelas IS NOT NULL AND kelas != ''
            ORDER BY kelas ASC
        ")->fetchAll(\PDO::FETCH_COLUMN);
    
        $siswaList = [];
    
        // Hanya query database KALAU salah satu filter diisi
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

        $message  = null;
        $user     = null;
        $sessions = [];
        $results  = [];

        $keyword = trim($_GET['q'] ?? '');
        $userId  = (int)($_GET['user_id'] ?? 0);

        // Cari siswa berdasarkan nama / username
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

        // Load siswa & sessions yang dipilih
        if ($userId > 0) {
            $stmt = db()->prepare("SELECT id, full_name, username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                $sessions = $this->fetchSessions($userId);
            }
        }

        // Proses reset (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_session_id']) && $user) {
            $sid = (int)$_POST['reset_session_id'];

            $stmt = db()->prepare("
                SELECT es.*, e.title, e.start_time, e.end_time,
                       (SELECT COUNT(*) FROM answers a WHERE a.session_id = es.id) AS answered_count
                FROM exam_sessions es
                JOIN exams e ON es.exam_id = e.id
                WHERE es.id = ? AND es.user_id = ?
            ");
            $stmt->execute([$sid, $user['id']]);
            $target = $stmt->fetch();

            if (!$target) {
                $message = ['type' => 'error', 'text' => '❌ Session tidak ditemukan.'];
            } elseif (!$this->isViolationSession($target)) {
                $message = ['type' => 'error', 'text' => '❌ Session ini bukan auto-submit/suspend karena pelanggaran.'];
            } else {
                $timeUntilEnd = max(0, strtotime($target['end_time']) - time());
                if ($timeUntilEnd <= 0) {
                    $message = ['type' => 'error', 'text' => '❌ Waktu ujian sudah berakhir. Tidak bisa dilanjutkan.'];
                } else {
                    $finalRemaining = $this->calcFinalRemaining($target);

                    db()->prepare("
                        UPDATE exam_sessions
                        SET status            = 'active',
                            token_status      = 'active',
                            submitted_at      = NULL,
                            score             = NULL,
                            is_best_score     = 0,
                            violation_count   = 0,
                            suspension_reason = NULL,
                            suspended_at      = NULL,
                            remaining_time    = ?
                        WHERE id = ? AND user_id = ?
                    ")->execute([$finalRemaining, $sid, $user['id']]);

                    db()->prepare("DELETE FROM exam_violations WHERE session_id = ?")
                        ->execute([$sid]);

                    $examTitle = htmlspecialchars($target['title'] ?? '');
                    $menit     = floor($finalRemaining / 60);
                    $detik     = $finalRemaining % 60;

                    $message = [
                        'type' => 'success',
                        'text' => "✅ Session ujian <b>\"$examTitle\"</b> berhasil direset! "
                                . "Jawaban siswa tetap tersimpan. "
                                . "Sisa waktu disesuaikan: <b>{$menit} menit {$detik} detik</b>. "
                                . "Minta siswa kembali ke halaman Daftar Ujian dan klik <b>\"Mulai Ujian\"</b> untuk melanjutkan.",
                    ];

                    $sessions = $this->fetchSessions($user['id']);
                }
            }
        }

        // Siapkan timer data untuk JS
        $serverNow = time();
        $timerData = [];
        foreach ($sessions as $s) {
            $canReset  = $this->isViolationSession($s);
            $examEnd   = strtotime($s['end_time']);
            $savedRem  = (int)$s['remaining_time'];

            $referenceTime = null;
            if (!empty($s['suspended_at'])) {
                $referenceTime = strtotime($s['suspended_at']);
            } elseif (!empty($s['submitted_at']) && $canReset) {
                $referenceTime = strtotime($s['submitted_at']);
            }

            if ($canReset) {
                $timerData[$s['id']] = [
                    'type'           => 'suspended',
                    'savedRemaining' => $savedRem,
                    'referenceTs'    => $referenceTime,
                    'endTs'          => $examEnd,
                    'serverNow'      => $serverNow,
                ];
            } elseif (
                !$s['submitted_at'] &&
                $s['status'] !== 'completed' &&
                in_array($s['status'], ['active', 'in_progress', 'suspended'])
            ) {
                $timerData[$s['id']] = [
                    'type'           => 'active',
                    'savedRemaining' => $savedRem,
                    'referenceTs'    => $serverNow,
                    'endTs'          => $examEnd,
                    'serverNow'      => $serverNow,
                ];
            }
        }

        view('admin/suspend-akun', [
            'message'   => $message,
            'keyword'   => $keyword,
            'userId'    => $userId,
            'results'   => $results,
            'user'      => $user,
            'sessions'  => $sessions,
            'timerData' => $timerData,
            'serverNow' => $serverNow,
        ]);
    }

    private function fetchSessions(int $userId): array
    {
        $stmt = db()->prepare("
            SELECT
                es.id,
                e.id   AS exam_id,
                e.title,
                e.start_time,
                e.end_time,
                es.status,
                es.submitted_at,
                es.score,
                es.remaining_time,
                es.violation_count,
                es.suspension_reason,
                es.suspended_at,
                (SELECT COUNT(*) FROM answers a WHERE a.session_id = es.id) AS answered_count,
                (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = es.exam_id) AS total_questions
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

        if (!empty($s['suspension_reason']) &&
            (stripos($s['suspension_reason'], 'pelanggaran') !== false ||
             stripos($s['suspension_reason'], 'auto')        !== false ||
             stripos($s['suspension_reason'], 'suspend')     !== false ||
             stripos($s['suspension_reason'], 'violation')   !== false)) {
            return true;
        }

        if ((int)$s['violation_count'] >= 1 && !empty($s['submitted_at']) && $s['status'] !== 'completed') {
            return true;
        }

        return false;
    }

    private function calcFinalRemaining(array $s): int
    {
        $now            = time();
        $savedRemaining = (int)$s['remaining_time'];

        $referenceTime = null;
        if (!empty($s['suspended_at'])) {
            $referenceTime = strtotime($s['suspended_at']);
        } elseif (!empty($s['submitted_at']) && $this->isViolationSession($s)) {
            $referenceTime = strtotime($s['submitted_at']);
        }

        if (!$referenceTime) {
            return max(60, $savedRemaining);
        }

        $elapsed      = max(0, $now - $referenceTime);
        $newRemaining = max(0, $savedRemaining - $elapsed);

        if ($newRemaining > $savedRemaining) $newRemaining = $savedRemaining;

        $examEnd      = strtotime($s['end_time']);
        $timeUntilEnd = max(0, $examEnd - $now);

        return max(60, min($newRemaining, $timeUntilEnd, $savedRemaining));
    }

    public function resetPassword()
    {
        requireAuth();
        requireRole(['admin']);

        $result  = null;
        $email   = trim($_POST['email'] ?? '');
        $newPass = $_POST['password'] ?? 'Ppdb2026!';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = db()->prepare("SELECT id, full_name, kelas FROM users WHERE username = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $result = ['error' => "Email <b>" . htmlspecialchars($email) . "</b> tidak ditemukan di CAT."];
            } else {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = db()->prepare("UPDATE users SET password = ?, kelas = 'PPDB-REGULER' WHERE id = ?");
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

    //=============== RESET SUBMIT ========================
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
                            $_SESSION['user']['username'] ?? '-', $email, $sid,
                            $target['title'], $target['submitted_at'], $target['score'], $minsAgo, $restored
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
                            'text' => "✅ Ujian <b>\"$judul\"</b> berhasil direset! "
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
                $message = ['type' => 'error', 'text' => '❌ Terjadi error. Cek error log server.'];
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
            'maxMinutes'=> self::MAX_RESET_MINUTES,
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
