<?php
/**
 * Cek struktur tabel database PPDB
 * Hapus file ini setelah selesai!
 */

$config = require __DIR__ . '/config.production.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=u245247469_ppdb_bkn;charset=utf8mb4',
        'u245247469_smaunggul',
        '1.SMAUnggul234!!',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>Tabel di database PPDB (u245247469_ppdb_bkn):</h2>";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "<h3>Tabel: <code>$table</code></h3>";
        echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "<tr>";
            foreach ($col as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Tampilkan 3 baris sample data
        $sample = $pdo->query("SELECT * FROM `$table` LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        if ($sample) {
            echo "<p><strong>Sample data:</strong></p><table border='1' cellpadding='5'>";
            echo "<tr>" . implode('', array_map(fn($k) => "<th>$k</th>", array_keys($sample[0]))) . "</tr>";
            foreach ($sample as $row) {
                echo "<tr>" . implode('', array_map(fn($v) => "<td>" . htmlspecialchars((string)$v) . "</td>", $row)) . "</tr>";
            }
            echo "</table>";
        }
        echo "<hr>";
    }

} catch (PDOException $e) {
    echo "<b>Gagal koneksi:</b> " . $e->getMessage();
}
