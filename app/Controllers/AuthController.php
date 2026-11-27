<?php

namespace App\Controllers;

                session_regenerate_id(true);

class AuthController
{
    public function login()
    {
        if (isLoggedIn()) {
            redirect('dashboard');
        }

        $error = null;

        // Pesan jika dipaksa logout karena login dari perangkat lain
        if (($_GET['reason'] ?? '') === 'concurrent') {
            $error = 'Sesi Anda dihentikan karena akun ini login dari perangkat lain.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Siswa WAJIB menggunakan ExamBro
                if (in_array($user['role'], ['student', 'siswa']) && !$this->isExamBrowser()) {
                    requireExamBro();
                }

                // ── Blokir login ganda untuk siswa ───────────────────────
                // if (in_array($user['role'], ['student', 'siswa'])) {
                //     $activeSessionId = $user['active_session_id'] ?? null;

                //     if ($activeSessionId && $activeSessionId !== session_id()) {
                //         $error = 'Akun ini masih aktif di perangkat lain. Tutup aplikasi di perangkat lain terlebih dahulu, kemudian coba login kembali.';
                //         view('login', ['error' => $error]);
                //         return;
                //     }
                // }
                // ── Akhir blokir login ganda ─────────────────────────────

                // Simpan session_id aktif ke DB (hanya jika kolom sudah ada)
                $newSessionId = session_id();
                try {
                    db()->prepare("UPDATE users SET active_session_id = ?, last_login_at = NOW() WHERE id = ?")
                        ->execute([$newSessionId, $user['id']]);
                } catch (\Exception $e) {
                    // Kolom mungkin belum ada (migration belum dijalankan) — abaikan, login tetap lanjut
                    error_log("active_session_id update skipped: " . $e->getMessage());
                }

                // Refresh data user setelah update
                $stmt2 = db()->prepare("SELECT * FROM users WHERE id = ?");
                $stmt2->execute([$user['id']]);
                $user = $stmt2->fetch();

                $_SESSION['user'] = $user;
                $_SESSION['active_session_id'] = $newSessionId;
                $_SESSION['success'] = 'Login berhasil!';
                redirect('dashboard');
            } else {
                $error = 'Username atau password salah!';
            }
        }

        view('login', ['error' => $error]);
    }

    public function logout()
    {
        // Hapus active_session_id dari DB agar akun bisa login di tempat lain
        $user = auth();
        if ($user) {
            db()->prepare("UPDATE users SET active_session_id = NULL WHERE id = ?")
                ->execute([$user['id']]);
        }

        session_destroy();
        redirect('auth/login');
    }
    private function isExamBrowser(): bool
    {
        $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $examHeader = $_SERVER['HTTP_X_EXAM_BROWSER'] ?? '';

        return strpos($userAgent, 'BKN-CAT-SecureBrowser') !== false
            || $examHeader === 'BKN-CAT-SecureBrowser';
    }
}
