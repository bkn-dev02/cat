<?php
// Reset / Create User Account
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ======================================
// DATA USER
// ======================================
$username     = 'tri@student.ubkn.sch.id';
$fullName     = 'Tri';
$kelas        = 'X-Volta';
$role         = 'siswa';
$newPassword  = 'Siswa123!';

echo "<h1>Reset / Create User</h1>";
echo "<hr>";

try {

    // ======================================
    // CEK USER
    // ======================================
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, kelas
        FROM users
        WHERE username = ?
    ");

    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ======================================
    // HASH PASSWORD
    // ======================================
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // ======================================
    // JIKA USER BELUM ADA
    // ======================================
    if (!$user) {

        $insert = $pdo->prepare("
            INSERT INTO users (
                username,
                password,
                full_name,
                role,
                kelas,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $insert->execute([
            $username,
            $email,
            $hashedPassword,
            $fullName,
            $role,
            $kelas
        ]);

        $userId = $pdo->lastInsertId();

        echo "
        <div style='background:#d4edda;padding:15px;border-radius:8px;margin-bottom:15px;'>
            <strong style='color:green;'>✓ Account berhasil dibuat!</strong><br>
            User ID: {$userId}
        </div>
        ";

    } else {

        // ======================================
        // UPDATE PASSWORD
        // ======================================
        $update = $pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE username = ?
        ");

        $update->execute([
            $hashedPassword,
            $username
        ]);

        echo "
        <div style='background:#d4edda;padding:15px;border-radius:8px;margin-bottom:15px;'>
            <strong style='color:green;'>✓ Password berhasil direset!</strong>
        </div>
        ";

        echo "
        <h3>Informasi User</h3>
        <table border='1' cellpadding='10' style='border-collapse:collapse;'>
            <tr>
                <td><strong>ID</strong></td>
                <td>{$user['id']}</td>
            </tr>
            <tr>
                <td><strong>Username</strong></td>
                <td>{$user['username']}</td>
            </tr>
            <tr>
                <td><strong>Nama</strong></td>
                <td>{$user['full_name']}</td>
            </tr>
            <tr>
                <td><strong>Kelas</strong></td>
                <td>{$user['kelas']}</td>
            </tr>
        </table>
        ";
    }

    // ======================================
    // LOGIN INFO
    // ======================================
    echo "<hr>";

    echo "
    <div style='background:#f8f9fa;border:1px solid #ddd;padding:20px;border-radius:10px;'>
        <h2 style='margin-top:0;'>Informasi Login</h2>

        <table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;'>
            <tr>
                <td width='150'><strong>URL Login</strong></td>
                <td>
                    <a href='{$config['app_url']}/auth/login'>
                        {$config['app_url']}/auth/login
                    </a>
                </td>
            </tr>

            <tr>
                <td><strong>Username</strong></td>
                <td>
                    <code style='background:#eee;padding:4px 8px;border-radius:4px;'>
                        {$username}
                    </code>
                </td>
            </tr>

            <tr>
                <td><strong>Password</strong></td>
                <td>
                    <code style='background:#eee;padding:4px 8px;border-radius:4px;'>
                        {$newPassword}
                    </code>
                </td>
            </tr>
        </table>
    </div>
    ";

} catch (Exception $e) {

    echo "
    <div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;'>
        <strong>Error:</strong><br>
        {$e->getMessage()}
    </div>
    ";
}

echo "<hr>";
echo "<a href='dashboard'>← Kembali ke Dashboard</a>";
?>