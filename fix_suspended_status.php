<?php
// Fix suspended sessions that have wrong status
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

echo "<h1>Fix Suspended Status</h1>";
echo "<hr>";

// Find sessions that are suspended but status is not 'suspended'
$stmt = $pdo->query("
    SELECT id, user_id, exam_id, status, submitted_at, suspended_at
    FROM exam_sessions
    WHERE suspended_at IS NOT NULL
    AND status != 'suspended'
");
$wrongStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Sessions with wrong status:</h2>";
echo "<p>Found: <strong>" . count($wrongStatus) . "</strong> sessions</p>";

if (count($wrongStatus) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Current Status</th><th>Submitted</th><th>Suspended</th></tr>";
    foreach ($wrongStatus as $s) {
        echo "<tr>";
        echo "<td>{$s['id']}</td>";
        echo "<td><strong>{$s['status']}</strong></td>";
        echo "<td>{$s['submitted_at']}</td>";
        echo "<td>{$s['suspended_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>Fixing...</h2>";
    
    // Update status to 'suspended'
    $stmt = $pdo->prepare("
        UPDATE exam_sessions
        SET status = 'suspended'
        WHERE suspended_at IS NOT NULL
        AND status != 'suspended'
    ");
    $stmt->execute();
    
    $updated = $stmt->rowCount();
    echo "<p style='color: green;'><strong>✓ Fixed {$updated} sessions!</strong></p>";
    echo "<p>Status changed from 'active' to 'suspended'</p>";
} else {
    echo "<p style='color: green;'>All suspended sessions have correct status!</p>";
}

echo "<hr>";
echo "<p><a href='debug_suspended_sessions.php'>Check Debug Page</a> | <a href='dashboard'>Back to Dashboard</a></p>";
