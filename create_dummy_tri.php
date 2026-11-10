<?php
// Create dummy account TRI
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "<h1>Create Dummy Account: TRI</h1>";
echo "<hr>";

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['tri']);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "<p style='color: orange;'>⚠️ User 'tri' already exists (ID: {$existing['id']})</p>";
        echo "<p>Deleting old account...</p>";
        
        // Delete old account
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute(['tri']);
        echo "<p style='color: green;'>✓ Old account deleted</p>";
    }
    
    // Create new account
    // Password: tri123 (hashed with bcrypt)
    $hashedPassword = password_hash('tri123', PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, full_name, email, role, kelas, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        'tri',
        $hashedPassword,
        'TRI',
        'tri@test.com',
        'siswa',
        'X-VOLTA'
    ]);
    
    $userId = $pdo->lastInsertId();
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>✓ Account Created Successfully!</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td><strong>User ID</strong></td><td>{$userId}</td></tr>";
    echo "<tr><td><strong>Username</strong></td><td>tri</td></tr>";
    echo "<tr><td><strong>Password</strong></td><td>tri123</td></tr>";
    echo "<tr><td><strong>Full Name</strong></td><td>TRI</td></tr>";
    echo "<tr><td><strong>Email</strong></td><td>tri@test.com</td></tr>";
    echo "<tr><td><strong>Role</strong></td><td>siswa</td></tr>";
    echo "<tr><td><strong>Kelas</strong></td><td>X-VOLTA</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>Login Information:</h3>";
    echo "<ul>";
    echo "<li>URL: <a href='" . $config['app_url'] . "/auth/login'>" . $config['app_url'] . "/auth/login</a></li>";
    echo "<li>Username: <strong>tri</strong></li>";
    echo "<li>Password: <strong>tri123</strong></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard'>Back to Dashboard</a></p>";
