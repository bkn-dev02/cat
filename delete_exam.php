<?php
$config = require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (isset($_GET['id'])) {
    $exam_id = $_GET['id'];
    
    // Get exam details first
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();
    
    if ($exam) {
        echo "<h2>HAPUS UJIAN</h2>";
        echo "<h3>Detail Ujian yang akan dihapus:</h3>";
        echo "<ul>";
        echo "<li>ID: {$exam['id']}</li>";
        echo "<li>Title: {$exam['title']}</li>";
        echo "<li>Status: {$exam['status']}</li>";
        echo "<li>Start: {$exam['start_time']}</li>";
        echo "</ul>";
        
        if (!isset($_GET['confirm'])) {
            echo "<p style='color:red'><strong>Apakah Anda yakin ingin menghapus ujian ini?</strong></p>";
            echo "<a href='delete_exam.php?id={$exam_id}&confirm=yes' style='padding:10px 20px; background:#f44336; color:white; text-decoration:none'>Ya, Hapus</a> ";
            echo "<a href='fix_duplicate_kedinasan.php' style='padding:10px 20px; background:#999; color:white; text-decoration:none'>Batal</a>";
        } else {
            // Delete exam (cascade will delete exam_participants and exam_subjects)
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$exam_id]);
            
            echo "<p style='color:green; font-size:20px'>✅ Ujian berhasil dihapus!</p>";
            echo "<p><a href='fix_duplicate_kedinasan.php'>Kembali</a></p>";
        }
    } else {
        echo "<p style='color:red'>Ujian tidak ditemukan</p>";
    }
} else {
    echo "<p style='color:red'>ID ujian tidak diberikan</p>";
}
