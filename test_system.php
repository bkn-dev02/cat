<?php
// Simple system test
echo "<!DOCTYPE html>";
echo "<html><head><title>System Test</title></head><body>";
echo "<h1>✓ PHP is Working!</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";

// Test database connection
try {
    require_once 'config.php';
    $db = db();
    echo "<p>✓ Database Connection: OK</p>";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM questions");
    $result = $stmt->fetch();
    echo "<p>✓ Total Questions: " . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p>✗ Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Quick Links:</h2>";
echo "<ul>";
echo "<li><a href='debug_upload.php'>Debug Upload Configuration</a></li>";
echo "<li><a href='index.php'>Main Application</a></li>";
echo "<li><a href='question/create'>Create Question (if logged in)</a></li>";
echo "</ul>";

echo "</body></html>";
?>
