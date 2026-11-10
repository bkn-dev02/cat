<?php

namespace App\Controllers;

class SyncController
{
    private $elearningDb;
    private $ppdbDb;

    public function __construct()
    {
        // Koneksi ke database e-learning
        try {
            $this->elearningDb = new \PDO(
                'mysql:host=' . config('elearning_db_host') . ';dbname=' . config('elearning_db_name') . ';charset=utf8mb4',
                config('elearning_db_user'),
                config('elearning_db_pass'),
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
        } catch (\PDOException $e) {
            $this->elearningDb = null;
        }

        // Koneksi ke database PPDB
        try {
            $this->ppdbDb = new \PDO(
                'mysql:host=' . config('ppdb_db_host') . ';dbname=' . config('ppdb_db_name') . ';charset=utf8mb4',
                config('ppdb_db_user'),
                config('ppdb_db_pass'),
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
        } catch (\PDOException $e) {
            $this->ppdbDb = null;
        }
    }
    
    public function students2()
    {
        // Cek role admin/guru
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
            header('Location: ' . config('app_url') . '/dashboard');
            exit;
        }
        
        $user = $_SESSION['user'];
        
        // Ambil data siswa dari e-learning
        try {
            // Ambil SEMUA siswa tanpa limit
            $stmt = $this->elearningDb->query("
                SELECT email, name, class_id, role
                FROM users 
                WHERE role = 'siswa' OR role = 'Siswa'
                ORDER BY name ASC
            ");
            $elearningStudents = $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Jika gagal, coba tanpa filter role
            try {
                $stmt = $this->elearningDb->query("SELECT email, name, class_id FROM users LIMIT 100");
                $elearningStudents = $stmt->fetchAll();
            } catch (\PDOException $e2) {
                $elearningStudents = [];
                $_SESSION['error'] = 'Gagal mengambil data dari e-learning: ' . $e2->getMessage();
            }
        }
        
        // Ambil data siswa yang sudah ada di CAT
        $stmt = db()->query("SELECT username FROM users WHERE role = 'siswa'");
        $existingStudents = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        require_once __DIR__ . '/../../views/sync/students_new.php';
    }
    
    public function syncStudents()
    {
        // Cek role admin/guru
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
            header('Location: ' . config('app_url') . '/dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app_url') . '/sync/students');
            exit;
        }
        
        $selectedStudents = $_POST['students'] ?? [];
        
        if (empty($selectedStudents)) {
            $_SESSION['error'] = 'Pilih minimal 1 siswa untuk di-sync';
            header('Location: ' . config('app_url') . '/sync/students');
            exit;
        }
        
        $synced = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($selectedStudents as $email) {
            try {
                // Ambil data siswa dari e-learning berdasarkan email
                $stmt = $this->elearningDb->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    $skipped++;
                    continue;
                }
                
                // Cek apakah sudah ada di CAT
                $stmt = db()->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$email]);
                $existing = $stmt->fetch();

                // Ambil data kelas dari e-learning
                $kelas = $student['class_id'] ?? null;

                if ($existing) {
                    // Siswa sudah ada — update kelasnya agar tetap sinkron
                    if ($kelas) {
                        try {
                            $updateKelas = db()->prepare("UPDATE users SET kelas = ? WHERE username = ?");
                            $updateKelas->execute([$kelas, $email]);
                        } catch (\Exception $e) {
                            // Silent — kolom kelas mungkin berbeda nama
                        }
                    }
                    $skipped++;
                    continue;
                }
                
                // Insert ke database CAT
                $stmt = db()->prepare("
                    INSERT INTO users (username, password, full_name, kelas, role, created_at)
                    VALUES (?, ?, ?, ?, 'siswa', NOW())
                ");
                
                // Handle berbagai kemungkinan nama kolom
                $username = $student['email'];
                $fullName = $student['name'] ?? $username;
                
                // Gunakan password dari e-learning atau default
                $password = $student['password'] ?? password_hash('Siswa123!', PASSWORD_DEFAULT);
                
                $stmt->execute([
                    $username,
                    $password,
                    $fullName,
                    $kelas
                ]);
                
                $synced++;
                
            } catch (\PDOException $e) {
                $errors[] = "Error sync {$username}: " . $e->getMessage();
            }
        }
        
        if ($synced > 0) {
            $_SESSION['success'] = "Berhasil sync {$synced} siswa dari e-learning";
        }
        
        if ($skipped > 0) {
            $_SESSION['info'] = "{$skipped} siswa dilewati (sudah ada atau tidak ditemukan)";
        }
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: ' . config('app_url') . '/sync/students2');
        exit;
    }
    
    public function resetsync()
    {
        // Cek role admin/guru
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
            header('Location: ' . config('app_url') . '/dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app_url') . '/sync/students2');
            exit;
        }
        
        try {
            // LANGKAH 1: Hapus semua siswa dari CAT
            $stmt = db()->prepare("DELETE FROM users WHERE role = 'siswa'");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            
            // LANGKAH 2: Ambil SEMUA siswa dari e-learning
            $stmt = $this->elearningDb->query("
                SELECT email, name, class_id, password
                FROM users 
                WHERE role = 'siswa' OR role = 'Siswa'
                ORDER BY name ASC
            ");
            $elearningStudents = $stmt->fetchAll();
            
            // LANGKAH 3: Insert semua siswa ke CAT
            $synced = 0;
            $errors = [];
            $defaultPassword = password_hash('Siswa123!', PASSWORD_DEFAULT);
            
            foreach ($elearningStudents as $student) {
                try {
                    // Pakai password e-learning jika ada, fallback ke default
                    $pw = (!empty($student['password']) && preg_match('/^\$2[ayb]\$/', $student['password']))
                          ? $student['password']
                          : $defaultPassword;

                    $stmt = db()->prepare("
                        INSERT INTO users (username, password, full_name, kelas, role, created_at)
                        VALUES (?, ?, ?, ?, 'siswa', NOW())
                    ");
                    
                    $stmt->execute([
                        $student['email'],
                        $pw,
                        $student['name'],
                        $student['class_id']
                    ]);
                    
                    $synced++;
                } catch (\PDOException $e) {
                    $errors[] = "Error: " . $student['email'];
                }
            }
            
            $_SESSION['success'] = "Reset berhasil! {$deleted} siswa lama dihapus, {$synced} siswa baru di-sync dari e-learning";
            
            if (!empty($errors)) {
                $_SESSION['warning'] = count($errors) . " siswa gagal di-sync";
            }
            
        } catch (\PDOException $e) {
            $_SESSION['error'] = 'Gagal reset siswa: ' . $e->getMessage();
        }
        
        header('Location: ' . config('app_url') . '/sync/students2');
        exit;
    }
    
    public function index()
    {
        // Redirect ke students2
        header('Location: ' . config('app_url') . '/sync/students2');
        exit;
    }

    /**
     * Re-sync exam_participants untuk semua ujian yang menggunakan kelas tertentu.
     * Berguna saat ada siswa baru yang bergabung ke kelas setelah ujian dibuat.
     * 
     * Akses: /sync/participants  (admin/guru only)
     */
    public function participants()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $user = auth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $examId   = (int)($_POST['exam_id'] ?? 0);
            $syncAll  = isset($_POST['sync_all']);

            try {
                $synced  = 0;
                $examsProcessed = 0;

                // Kumpulkan ujian yang akan di-sync
                if ($syncAll) {
                    // Semua ujian yang punya peserta terbatas (exam_participants tidak kosong)
                    $stmt = db()->query("
                        SELECT DISTINCT exam_id FROM exam_participants
                    ");
                    $examIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                } elseif ($examId > 0) {
                    $examIds = [$examId];
                } else {
                    $_SESSION['error'] = 'Pilih ujian atau sync semua.';
                    redirect('sync/participants');
                    return;
                }

                foreach ($examIds as $eid) {
                    // Ambil kelas yang sudah terdaftar di ujian ini
                    $stmtKelas = db()->prepare("
                        SELECT DISTINCT u.kelas
                        FROM exam_participants ep
                        JOIN users u ON u.id = ep.user_id
                        WHERE ep.exam_id = ? AND u.role = 'siswa' AND u.kelas IS NOT NULL
                    ");
                    $stmtKelas->execute([$eid]);
                    $kelas = $stmtKelas->fetchAll(\PDO::FETCH_COLUMN);

                    if (empty($kelas)) continue;

                    // Ambil semua siswa di kelas tersebut yang belum ada di exam_participants
                    $ph = implode(',', array_fill(0, count($kelas), '?'));
                    $stmtNew = db()->prepare("
                        SELECT u.id FROM users u
                        WHERE u.role = 'siswa'
                          AND u.kelas IN ($ph)
                          AND u.id NOT IN (
                              SELECT user_id FROM exam_participants WHERE exam_id = ?
                          )
                    ");
                    $stmtNew->execute(array_merge($kelas, [$eid]));
                    $newStudents = $stmtNew->fetchAll(\PDO::FETCH_COLUMN);

                    if (!empty($newStudents)) {
                        $stmtIns = db()->prepare("
                            INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)
                        ");
                        foreach ($newStudents as $uid) {
                            $stmtIns->execute([$eid, $uid]);
                            $synced++;
                        }
                        $examsProcessed++;
                    }
                }

                if ($synced > 0) {
                    $_SESSION['success'] = "Berhasil menambahkan <b>{$synced}</b> peserta baru ke <b>{$examsProcessed}</b> ujian.";
                } else {
                    $_SESSION['success'] = "Semua ujian sudah sinkron — tidak ada peserta baru yang perlu ditambahkan.";
                }

            } catch (\Exception $e) {
                $_SESSION['error'] = 'Gagal sync peserta: ' . $e->getMessage();
            }

            redirect('sync/participants');
            return;
        }

        // GET — tampilkan halaman
        // Daftar ujian yang punya peserta terbatas
        $stmt = db()->prepare("
            SELECT e.id, e.title, e.start_time, e.end_time,
                   COUNT(ep.id) as participant_count,
                   GROUP_CONCAT(DISTINCT u.kelas ORDER BY u.kelas SEPARATOR ', ') as kelas_list
            FROM exams e
            JOIN exam_participants ep ON ep.exam_id = e.id
            JOIN users u ON u.id = ep.user_id AND u.role = 'siswa'
            WHERE e.status = 'published'
            GROUP BY e.id, e.title, e.start_time, e.end_time
            ORDER BY e.start_time DESC
        ");
        $stmt->execute();
        $exams = $stmt->fetchAll();

        view('sync/participants', [
            'user'  => $user,
            'exams' => $exams,
        ]);
    }

    // =============================================
    // SYNC DARI PPDB - JALUR REGULER
    // =============================================

    public function ppdb()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
            header('Location: ' . config('app_url') . '/dashboard');
            exit;
        }

        $ppdbStudents = [];
        $ppdbError = null;

        if ($this->ppdbDb === null) {
            $ppdbError = 'Gagal koneksi ke database PPDB. Pastikan user database sudah di-grant akses SELECT di hPanel Hostinger.';
        } else {
            try {
                $stmt = $this->ppdbDb->query("
                    SELECT u.id, u.email, u.password,
                           s.nama_lengkap, s.nisn, s.nama_sekolah_asal,
                           r.jalur
                    FROM users u
                    JOIN students s ON s.user_id = u.id
                    JOIN registrations r ON r.user_id = u.id
                    WHERE r.jalur = 'reguler'
                    ORDER BY s.nama_lengkap ASC
                ");
                $ppdbStudents = $stmt->fetchAll();
            } catch (\PDOException $e) {
                $ppdbError = 'Gagal mengambil data PPDB: ' . $e->getMessage();
            }
        }

        // Ambil username yang sudah ada di CAT
        $stmt = db()->query("SELECT username FROM users WHERE role = 'siswa'");
        $existingUsernames = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        require_once __DIR__ . '/../../views/sync/ppdb.php';
    }

    public function syncPpdb()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
            header('Location: ' . config('app_url') . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app_url') . '/sync/ppdb');
            exit;
        }

        if ($this->ppdbDb === null) {
            $_SESSION['error'] = 'Gagal koneksi ke database PPDB.';
            header('Location: ' . config('app_url') . '/sync/ppdb');
            exit;
        }

        $selectedIds = $_POST['students'] ?? [];

        if (empty($selectedIds)) {
            $_SESSION['error'] = 'Pilih minimal 1 siswa untuk di-sync.';
            header('Location: ' . config('app_url') . '/sync/ppdb');
            exit;
        }

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($selectedIds as $userId) {
            try {
                $stmt = $this->ppdbDb->prepare("
                    SELECT u.email, u.password,
                           s.nama_lengkap, s.nisn
                    FROM users u
                    JOIN students s ON s.user_id = u.id
                    JOIN registrations r ON r.user_id = u.id
                    WHERE u.id = ? AND r.jalur = 'reguler'
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                $student = $stmt->fetch();

                if (!$student) {
                    $skipped++;
                    continue;
                }

                // Username pakai email
                $username = $student['email'];

                // Cek sudah ada di CAT
                $check = db()->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    $skipped++;
                    continue;
                }

                // Insert ke CAT — password diambil dari PPDB (sudah di-hash)
                $insert = db()->prepare("
                    INSERT INTO users (username, password, full_name, kelas, role, created_at)
                    VALUES (?, ?, ?, 'X', 'siswa', NOW())
                ");
                $insert->execute([
                    $username,
                    $student['password'],
                    $student['nama_lengkap'] ?? $username,
                ]);

                $synced++;

            } catch (\PDOException $e) {
                $errors[] = "Error user_id {$userId}: " . $e->getMessage();
            }
        }

        if ($synced > 0) {
            $_SESSION['success'] = "Berhasil sync {$synced} siswa dari PPDB jalur reguler.";
        }
        if ($skipped > 0) {
            $_SESSION['info'] = "{$skipped} siswa dilewati (sudah ada di CAT).";
        }
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
        }

        header('Location: ' . config('app_url') . '/sync/ppdb');
        exit;
    }
    
    // syncronkan data user e-recruitment yang akan ujian akademik
    private function getRecruitmentApplicants()
    {
        $ch = curl_init();
    
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://e-recruitment.binakasihnusantara.sch.id/api/applicants/akademik',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-API-KEY: 0aae528d1b27274e60cd0f747172ad3a43fb94d10c2761f0463827680d2a287c',
            ],
        ]);
    
        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
    
        curl_close($ch);
    
        $data = json_decode($response, true);
    
        return $data['data'] ?? [];
    }
    
    
    public function recruitment()
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
            header('Location: ' . config('app_url') . '/dashboard');
            exit;
        }
    
        try {
    
            $recruitmentApplicants = $this->getRecruitmentApplicants();
    
            $existingUsers = db()
                ->query("SELECT username FROM users")
                ->fetchAll(\PDO::FETCH_COLUMN);
    
            $existingUsers = array_flip($existingUsers);
    
            foreach ($recruitmentApplicants as &$applicant) {
    
                $applicant['is_synced'] = isset(
                    $existingUsers[$applicant['email']]
                );
            }
    
            unset($applicant);
    
        } catch (\Exception $e) {
    
            $recruitmentApplicants = [];
            $_SESSION['error'] = $e->getMessage();
        }
    
        require_once __DIR__ . '/../../views/sync/recruitment.php';
    }
    
    public function syncRecruitment()
    {
        // Cek login dan role
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'siswa') {
            header('Location: ' . config('app_url') . '/dashboard');
            exit;
        }
    
        // Harus method POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . config('app_url') . '/sync/recruitment');
            exit;
        }
    
        $selectedIds = $_POST['students'] ?? [];
    
        if (empty($selectedIds)) {
            $_SESSION['error'] = 'Pilih minimal 1 pelamar untuk di-sync.';
            header('Location: ' . config('app_url') . '/sync/recruitment');
            exit;
        }
    
        $synced = 0;
        $skipped = 0;
        $errors = [];
    
        // Ambil data lengkap dari API
        try {
            $allApplicants = $this->getRecruitmentApplicants();
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal mengambil data dari e-recruitment: ' . $e->getMessage();
            header('Location: ' . config('app_url') . '/sync/recruitment');
            exit;
        }
    
        // Buat mapping application_id ke data applicant
        $applicantMap = [];
        foreach ($allApplicants as $applicant) {
            $applicantMap[$applicant['application_id']] = $applicant;
        }
    
        try {

            $db = db();
    
            // Ambil seluruh username yang sudah ada
            $existingUsers = $db
                ->query("SELECT username FROM users")
                ->fetchAll(\PDO::FETCH_COLUMN);
    
            $existingUsers = array_flip($existingUsers);
    
            // Hash password sekali saja
            $defaultPassword = password_hash(
                'CalonGuruStaff2026!',
                PASSWORD_DEFAULT
            );
    
            // Prepare sekali saja
            $insert = $db->prepare("
                INSERT INTO users (
                    username,
                    password,
                    full_name,
                    kelas,
                    role,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");
    
            $db->beginTransaction();
    
            foreach ($selectedIds as $applicationId) {
    
                if (!isset($applicantMap[$applicationId])) {
                    $errors[] = "Application ID {$applicationId} tidak ditemukan di data e-recruitment.";
                    $skipped++;
                    continue;
                }
    
                $applicant = $applicantMap[$applicationId];
    
                $username = trim($applicant['email']);
                $fullName = trim($applicant['full_name']);
    
                // Sudah ada di CAT
                if (isset($existingUsers[$username])) {
                    $skipped++;
                    continue;
                }
    
                $insert->execute([
                    $username,
                    $defaultPassword,
                    $fullName,
                    'CALON-GURU',
                    'siswa',
                    date('Y-m-d H:i:s')
                ]);
    
                // Tambahkan ke cache agar tidak insert dua kali
                $existingUsers[$username] = true;
    
                $synced++;
            }
    
            $db->commit();
    
        } catch (\PDOException $e) {
    
            if ($db->inTransaction()) {
                $db->rollBack();
            }
    
            $errors[] = $e->getMessage();
        }
    
        // Set session messages
        if ($synced > 0) {
            $_SESSION['success'] = "Berhasil sync {$synced} pelamar dari e-recruitment ke sistem CAT.";
        }
        if ($skipped > 0) {
            $_SESSION['info'] = "{$skipped} pelamar dilewati (sudah terdaftar di CAT).";
        }
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    
        header('Location: ' . config('app_url') . '/sync/recruitment');
        exit;
    }
}
