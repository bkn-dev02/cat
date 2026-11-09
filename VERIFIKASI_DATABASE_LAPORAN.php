<?php
/**
 * Verifikasi Database untuk Fitur Laporan PDF
 * Run: php VERIFIKASI_DATABASE_LAPORAN.php
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Jakarta');

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');

spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . str_replace('\\', '/', str_replace('App\\', '', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once APP_PATH . '/helpers.php';

echo "═══════════════════════════════════════════════════════════\n";
echo "  VERIFIKASI DATABASE UNTUK FITUR LAPORAN PDF\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // Test database connection
    echo "1. Testing database connection... ";
    $pdo = db();
    echo "✓ OK\n\n";

    // Check tables
    echo "2. Checking required tables:\n";
    
    $tables = ['exams', 'exam_sessions', 'questions', 'answers', 'users', 'subjects'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "   - $table: " . ($exists ? "✓ OK" : "✗ MISSING") . "\n";
    }
    
    echo "\n3. Checking table structures:\n";
    
    // Check exams table
    echo "   - exams table:\n";
    $stmt = $pdo->query("DESCRIBE exams");
    $columns = $stmt->fetchAll();
    $requiredCols = ['id', 'title', 'subject_id', 'created_at'];
    foreach ($requiredCols as $col) {
        $found = array_filter($columns, fn($c) => $c['Field'] === $col);
        echo "     • $col: " . (count($found) > 0 ? "✓" : "✗") . "\n";
    }
    
    // Check exam_sessions table
    echo "   - exam_sessions table:\n";
    $stmt = $pdo->query("DESCRIBE exam_sessions");
    $columns = $stmt->fetchAll();
    $requiredCols = ['id', 'exam_id', 'user_id', 'score', 'status', 'submitted_at'];
    foreach ($requiredCols as $col) {
        $found = array_filter($columns, fn($c) => $c['Field'] === $col);
        echo "     • $col: " . (count($found) > 0 ? "✓" : "✗") . "\n";
    }
    
    // Check questions table
    echo "   - questions table:\n";
    $stmt = $pdo->query("DESCRIBE questions");
    $columns = $stmt->fetchAll();
    $requiredCols = ['id', 'exam_id', 'question_number', 'question_text', 'correct_answer'];
    foreach ($requiredCols as $col) {
        $found = array_filter($columns, fn($c) => $c['Field'] === $col);
        echo "     • $col: " . (count($found) > 0 ? "✓" : "✗") . "\n";
    }
    
    // Check answers table
    echo "   - answers table:\n";
    $stmt = $pdo->query("DESCRIBE answers");
    $columns = $stmt->fetchAll();
    $requiredCols = ['id', 'session_id', 'question_id', 'answer'];
    foreach ($requiredCols as $col) {
        $found = array_filter($columns, fn($c) => $c['Field'] === $col);
        echo "     • $col: " . (count($found) > 0 ? "✓" : "✗") . "\n";
    }
    
    echo "\n4. Checking data:\n";
    
    // Count exams
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM exams");
    $result = $stmt->fetch();
    echo "   - Total exams: " . $result['count'] . "\n";
    
    // Count exam_sessions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM exam_sessions WHERE status IN ('submitted', 'graded', 'completed', 'suspended')");
    $result = $stmt->fetch();
    echo "   - Total submitted sessions: " . $result['count'] . "\n";
    
    // Count questions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM questions");
    $result = $stmt->fetch();
    echo "   - Total questions: " . $result['count'] . "\n";
    
    // Count answers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM answers");
    $result = $stmt->fetch();
    echo "   - Total answers: " . $result['count'] . "\n";
    
    echo "\n5. Sample data check:\n";
    
    // Get sample exam
    $stmt = $pdo->query("SELECT id, title FROM exams LIMIT 1");
    $exam = $stmt->fetch();
    if ($exam) {
        echo "   - Sample exam: " . $exam['title'] . " (ID: " . $exam['id'] . ")\n";
        
        // Get sessions for this exam
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM exam_sessions WHERE exam_id = ? AND status IN ('submitted', 'graded', 'completed', 'suspended')");
        $stmt->execute([$exam['id']]);
        $result = $stmt->fetch();
        echo "     • Sessions: " . $result['count'] . "\n";
        
        // Get questions for this exam
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE exam_id = ?");
        $stmt->execute([$exam['id']]);
        $result = $stmt->fetch();
        echo "     • Questions: " . $result['count'] . "\n";
    } else {
        echo "   - No exams found in database\n";
    }
    
    echo "\n6. Dependencies check:\n";
    if (file_exists('vendor/autoload.php')) {
        echo "   - vendor/autoload.php: ✓ OK\n";
        if (file_exists('vendor/tecnickcom/tcpdf/tcpdf.php')) {
            echo "   - TCPDF library: ✓ OK\n";
        } else {
            echo "   - TCPDF library: ✗ NOT FOUND\n";
            echo "     Run: php install_dependencies.php\n";
        }
    } else {
        echo "   - vendor/autoload.php: ✗ NOT FOUND\n";
        echo "     Run: php install_dependencies.php\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "✓ Verifikasi selesai!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check database connection di config.php\n";
    echo "2. Pastikan database sudah dibuat\n";
    echo "3. Pastikan tables sudah ada\n";
    echo "4. Check error_log untuk detail error\n";
}
