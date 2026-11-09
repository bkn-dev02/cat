<?php

namespace App\Controllers;

class ProfileController
{
    public function changePassword()
    {
        requireAuth();

        $user = auth();
        $success = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Ambil password hash dari DB
            $stmt = db()->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($currentPassword, $row['password'])) {
                $error = 'Password saat ini tidak benar.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password baru minimal 8 karakter.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Konfirmasi password tidak cocok.';
            } else {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                db()->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([$hashed, $user['id']]);

                // Update session dengan data terbaru
                $stmt2 = db()->prepare("SELECT * FROM users WHERE id = ?");
                $stmt2->execute([$user['id']]);
                $_SESSION['user'] = $stmt2->fetch();

                $success = 'Password berhasil diubah!';
            }
        }

        view('profile/change-password', [
            'success' => $success,
            'error'   => $error,
        ]);
    }
}
