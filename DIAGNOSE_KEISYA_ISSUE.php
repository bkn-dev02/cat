<?php
// DIAGNOSE KEISYA ANGGITA SUBMISSION ISSUE
// Check all possible data points for this student

require_once 'config.php';

echo "<h2>DIAGNOSTIC REPORT: KEISYA ANGGITA BR SEMBIRING</h2>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// 1. Find user
echo "<h3>1. USER DATA</h3>";
$stmt = db()->prepare("
    SELECT id, username, full_name, kelas, role, created_at
    FROM users
    WHERE full_name LIKE '%KEISYA%' 
       OR username LIKE '%keisya%'
");
$stmt->execute();
$users = $stmt->fetchAll();

if (empty($users)) {
    echo "<p style='color: red;'>❌ USER NOT FOUND</p>";
} else {
    foreach ($users as $user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        $userId = $user['id'];
    }
}

if (!isset($userId)) {
    die("<p style='color: red;'>Cannot continue without user ID</p>");
}

// 2. Check exam sessions
echo "<h3>2. EXAM SESSIONS (ALL TIME)</h3>";
$stmt = db()->prepare("
    SELECT es.*, e.title as exam_title
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    WHERE es.user_id = ?
    ORDER BY es.started_at DESC
");
$stmt->execute([$userId]);
$sessions = $stmt->fetchAll();

if (empty($sessions)) {
    echo "<p style='color: red;'>❌ NO SESSIONS FOUND FOR THIS USER</p>";
} else {
    echo "<p style='color: green;'>✓ Found " . count($sessions) . " session(s)</p>";
    foreach ($sessions as $session) {
        echo "<pre>";
        print_r($session);
        echo "</pre>";
    }
}

// 3. Check answers (if any sessions exist)
if (!empty($sessions)) {
    echo "<h3>3. ANSWERS DATA</h3>";
    foreach ($sessions as $session) {
        $stmt = db()->prepare("
            SELECT COUNT(*) as count
            FROM answers
            WHERE session_id = ?
        ");
        $stmt->execute([$session['id']]);
        $answerCount = $stmt->fetch()['count'];
        echo "<p>Session {$session['id']}: {$answerCount} answers</p>";
    }
}

// 4. Check exam participants
echo "<h3>4. EXAM PARTICIPANTS</h3>";
$stmt = db()->prepare("
    SELECT ep.*, e.title as exam_title
    FROM exam_participants ep
    JOIN exams e ON ep.exam_id = e.id
    WHERE ep.user_id = ?
");
$stmt->execute([$userId]);
$participants = $stmt->fetchAll();

if (empty($participants)) {
    echo "<p style='color: orange;'>⚠ User not assigned to any exams via exam_participants</p>";
} else {
    echo "<p style='color: green;'>✓ User assigned to " . count($participants) . " exam(s)</p>";
    foreach ($participants as $p) {
        echo "<pre>";
        print_r($p);
        echo "</pre>";
    }
}

// 5. Check recent exams that are active
echo "<h3>5. ACTIVE EXAMS TODAY</h3>";
$stmt = db()->prepare("
    SELECT id, title, start_time, end_time, status
    FROM exams
    WHERE DATE(start_time) = CURDATE()
       OR DATE(end_time) = CURDATE()
    ORDER BY start_time DESC
");
$stmt->execute();
$activeExams = $stmt->fetchAll();

echo "<p>Found " . count($activeExams) . " exam(s) active today:</p>";
foreach ($activeExams as $exam) {
    echo "<pre>";
    print_r($exam);
    echo "</pre>";
}

// 6. Check error logs (if accessible)
echo "<h3>6. PHP ERROR LOG CHECK</h3>";
$errorLogPath = ini_get('error_log');
echo "<p>Error log location: " . ($errorLogPath ?: 'default location') . "</p>";

// 7. CONCLUSION
echo "<hr>";
echo "<h3>CONCLUSION</h3>";

if (empty($sessions)) {
    echo "<div style='background: #fee; padding: 20px; border-left: 4px solid red;'>";
    echo "<h4 style='color: red;'>CRITICAL: NO SESSION DATA FOUND</h4>";
    echo "<p><strong>This means:</strong></p>";
    echo "<ul>";
    echo "<li>Student's exam session was NEVER created in database</li>";
    echo "<li>OR session was created but deleted/lost</li>";
    echo "<li>Student's answers were NEVER saved</li>";
    echo "<li>NO RECOVERY POSSIBLE - Student must retake exam</li>";
    echo "</ul>";
    echo "<p><strong>Possible causes:</strong></p>";
    echo "<ul>";
    echo "<li>Database connection error during exam start</li>";
    echo "<li>Session creation failed silently</li>";
    echo "<li>Browser/network issue prevented initial session creation</li>";
    echo "<li>Student may have started exam but session creation failed</li>";
    echo "</ul>";
    echo "<p><strong>Recommended action:</strong></p>";
    echo "<ul>";
    echo "<li>Check server error logs for database errors around exam time</li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Allow student to retake exam</li>";
    echo "<li>Investigate ExamController::start() method for error handling</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #efe; padding: 20px; border-left: 4px solid green;'>";
    echo "<h4 style='color: green;'>SESSION DATA FOUND</h4>";
    echo "<p>Recovery may be possible. Check session status and answers.</p>";
    echo "</div>";
}
?>
