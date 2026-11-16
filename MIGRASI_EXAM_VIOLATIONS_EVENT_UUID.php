<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Jalankan migrasi ini melalui PHP CLI.\n");
}

require_once __DIR__ . '/app/helpers.php';

$pdo = db();

try {
    if (!$pdo->query("SHOW TABLES LIKE 'exam_violations'")->fetchColumn()) {
        throw new RuntimeException('Tabel exam_violations belum tersedia. Jalankan INSTALL_ANTI_CHEATING.php terlebih dahulu.');
    }

    $column = $pdo->query("SHOW COLUMNS FROM exam_violations LIKE 'event_uuid'")->fetch();
    if (!$column) {
        $pdo->exec("
            ALTER TABLE exam_violations
            ADD COLUMN event_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL
            AFTER session_id
        ");
        echo "Kolom event_uuid ditambahkan.\n";
    } else {
        echo "Kolom event_uuid sudah tersedia.\n";
    }

    $duplicates = $pdo->query("
        SELECT session_id, event_uuid, COUNT(*) AS duplicate_count
        FROM exam_violations
        WHERE event_uuid IS NOT NULL
        GROUP BY session_id, event_uuid
        HAVING COUNT(*) > 1
        LIMIT 1
    ")->fetch();
    if ($duplicates) {
        throw new RuntimeException('Ada event_uuid duplikat; unique index tidak dapat dipasang dengan aman.');
    }

    $indexes = $pdo->query("SHOW INDEX FROM exam_violations")->fetchAll();
    $hasUniqueIndex = false;
    foreach ($indexes as $index) {
        if ($index['Key_name'] === 'uq_exam_violations_session_event') {
            $hasUniqueIndex = true;
            break;
        }
    }
    if (!$hasUniqueIndex) {
        $pdo->exec("
            ALTER TABLE exam_violations
            ADD UNIQUE KEY uq_exam_violations_session_event (session_id, event_uuid)
        ");
        echo "Unique index event pelanggaran ditambahkan.\n";
    } else {
        echo "Unique index event pelanggaran sudah tersedia.\n";
    }

    $pdo->beginTransaction();
    $updated = $pdo->exec("
        UPDATE exam_sessions es
        LEFT JOIN (
            SELECT session_id, COUNT(*) AS history_count
            FROM exam_violations
            GROUP BY session_id
        ) v ON v.session_id = es.id
        SET es.violation_count = COALESCE(v.history_count, 0)
        WHERE NOT (es.violation_count <=> COALESCE(v.history_count, 0))
    ");
    $pdo->commit();

    echo "Migrasi selesai; ringkasan sesi disinkronkan pada {$updated} baris.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Migrasi gagal: " . $e->getMessage() . "\n");
    exit(1);
}
