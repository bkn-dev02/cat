<?php
// Debug suspended sessions
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database connection
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

echo "<h1>Debug Suspended Sessions</h1>";
echo "<hr>";

try {
    // Check all sessions with submitted_at
    echo "<h2>1. All Sessions with submitted_at</h2>";
    $stmt = $pdo->query("
        SELECT 
            es.id,
            es.user_id,
            u.full_name,
            u.kelas,
            e.title as exam_title,
            es.status,
            es.score,
            es.submitted_at,
            es.suspended_at,
            es.is_best_score,
            (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        WHERE es.submitted_at IS NOT NULL
        ORDER BY es.submitted_at DESC
        LIMIT 20
    ");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found: <strong>" . count($sessions) . "</strong> sessions</p>";
    
    if (count($sessions) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>
            <th>ID</th>
            <th>Siswa</th>
            <th>Kelas</th>
            <th>Ujian</th>
            <th>Status</th>
            <th>Score</th>
            <th>Submitted At</th>
            <th>Suspended At</th>
            <th>Is Best</th>
            <th>Answers</th>
        </tr>";
        
        foreach ($sessions as $s) {
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td>{$s['full_name']}</td>";
            echo "<td>{$s['kelas']}</td>";
            echo "<td>{$s['exam_title']}</td>";
            echo "<td><strong>{$s['status']}</strong></td>";
            echo "<td>{$s['score']}</td>";
            echo "<td>{$s['submitted_at']}</td>";
            echo "<td>" . ($s['suspended_at'] ?? 'NULL') . "</td>";
            echo "<td>" . ($s['is_best_score'] ?? 'NULL') . "</td>";
            echo "<td>{$s['answer_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No sessions found with submitted_at!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error in query 1: " . $e->getMessage() . "</p>";
}

echo "<hr>";

try {
    echo "<h2>2. Suspended Sessions Only</h2>";
    $stmt = $pdo->query("
        SELECT 
            es.id,
            u.full_name,
            e.title,
            es.status,
            es.score,
            es.submitted_at,
            es.suspended_at
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        WHERE es.status = 'suspended'
        ORDER BY es.id DESC
    ");
    $suspended = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total suspended sessions: <strong>" . count($suspended) . "</strong></p>";
    
    if (count($suspended) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Siswa</th><th>Ujian</th><th>Status</th><th>Score</th><th>Submitted</th><th>Suspended</th></tr>";
        foreach ($suspended as $s) {
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td>{$s['full_name']}</td>";
            echo "<td>{$s['title']}</td>";
            echo "<td>{$s['status']}</td>";
            echo "<td>{$s['score']}</td>";
            echo "<td>" . ($s['submitted_at'] ?? 'NULL') . "</td>";
            echo "<td>" . ($s['suspended_at'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No suspended sessions found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error in query 2: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard'>Back to Dashboard</a></p>";
