<?php
// Check PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Check System Status</h1>";

// Test database
try {
    require_once 'config.php';
    $db = db();
    echo "<p>✓ Database: OK</p>";
    
    // Test insert
    $testData = [
        'subject_id' => 1,
        'type' => 'multiple_choice',
        'question_text' => 'TEST QUESTION - DELETE ME',
        'question_image' => null,
        'option_a' => 'A',
        'option_b' => 'B',
        'option_c' => 'C',
        'option_d' => 'D',
        'option_e' => 'E',
        'correct_answer' => 'a',
        'hide_correct_answer' => 0,
        'points' => 1,
        'created_by' => 1
    ];
    
    $stmt = $db->prepare("
        INSERT INTO questions (subject_id, type, question_text, question_image, option_a, option_b, option_c, option_d, option_e, correct_answer, hide_correct_answer, points, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute(array_values($testData));
    
    if ($result) {
        $lastId = $db->lastInsertId();
        echo "<p>✓ Insert Test: OK (ID: $lastId)</p>";
        
        // Delete test data
        $db->prepare("DELETE FROM questions WHERE id = ?")->execute([$lastId]);
        echo "<p>✓ Delete Test: OK</p>";
    } else {
        echo "<p>✗ Insert Test: FAILED</p>";
        print_r($stmt->errorInfo());
    }
    
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>Kemungkinan Masalah:</h2>";
echo "<ol>";
echo "<li>Database connection timeout</li>";
echo "<li>Table 'questions' locked</li>";
echo "<li>Trigger atau constraint error</li>";
echo "<li>Session conflict</li>";
echo "</ol>";
?>
