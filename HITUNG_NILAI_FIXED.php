<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>HITUNG ULANG NILAI - VERSI FIXED</h2>";

// 1. Cek struktur answers terlebih dahulu
echo "<h3>1. CEK STRUKTUR TABEL ANSWERS:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE answers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    $answer_columns = [];
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        $answer_columns[] = $col['Field'];
    }
    echo "</table>";
    echo "<p>Kolom yang tersedia: " . implode(", ", $answer_columns) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 2. Cek sample data answers
echo "<h3>2. SAMPLE DATA ANSWERS:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM answers LIMIT 3");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($samples);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 3. Cek struktur exam_questions
echo "<h3>3. CEK STRUKTUR TABEL EXAM_QUESTIONS:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE exam_questions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    $question_columns = [];
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        $question_columns[] = $col['Field'];
    }
    echo "</table>";
    echo "<p>Kolom yang tersedia: " . implode(", ", $question_columns) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 4. Cek sample data exam_questions
echo "<h3>4. SAMPLE DATA EXAM_QUESTIONS:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM exam_questions LIMIT 3");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($samples);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 5. Cek tabel questions
echo "<h3>5. CEK STRUKTUR TABEL QUESTIONS:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE questions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 6. Sample data questions
echo "<h3>6. SAMPLE DATA QUESTIONS:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM questions LIMIT 3");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($samples);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
