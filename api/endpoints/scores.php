<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api.php';

/**
 * GET /api/scores
 */
function handleScoresList(): void {
    $db = getDB();

    $role     = $_GET['role']      ?? null;
    $examId   = $_GET['exam_id']   ?? null;
    $bestOnly = $_GET['best_only'] ?? null;
    $page     = max(1, (int)($_GET['page']  ?? 1));
    $limit    = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $offset   = ($page - 1) * $limit;

    if ($role && !in_array($role, ALLOWED_ROLES, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'role tidak valid. Gunakan: guru atau staff']);
        return;
    }

    $roles        = $role ? [$role] : ALLOWED_ROLES;
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $params       = $roles;
    $where        = ["u.role IN ($placeholders)", "es.status = 'submitted'"];

    if ($examId) {
        $where[]  = "es.exam_id = ?";
        $params[] = (int)$examId;
    }
    if ($bestOnly) {
        $where[] = "es.is_best_score = 1";
    }

    $whereSQL = implode(' AND ', $where);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM exam_sessions es JOIN users u ON u.id = es.user_id WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT
            es.id              AS session_id,
            es.exam_id,
            es.score,
            es.is_best_score,
            es.started_at,
            es.submitted_at,
            es.violation_count,
            u.id               AS user_id,
            u.full_name,
            u.username,
            u.role
        FROM exam_sessions es
        JOIN users u ON u.id = es.user_id
        WHERE $whereSQL
        ORDER BY es.submitted_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success'    => true,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
        ],
        'data' => $data,
    ]);
}

/**
 * GET /api/scores/{category}
 * Daftar ujian yang tersedia untuk kategori tertentu
 */
function handleExamsByCategory(string $category): void {
    $db = getDB();

    if ($category === 'calon-guru') {
        $kelasWhere = "u.kelas = 'CALON-GURU'";
    } elseif ($category === 'ppdb') {
        $kelasWhere = "u.kelas = 'PPDB-REGULER'";
    } else {
        $kelasWhere = "u.kelas NOT IN ('CALON-GURU', 'PPDB-REGULER')";
    }

    $stmt = $db->prepare("
        SELECT
            e.id            AS exam_id,
            e.title,
            e.exam_type,
            e.category,
            COUNT(DISTINCT es.user_id) AS total_peserta
        FROM exam_sessions es
        JOIN users u ON u.id = es.user_id
        JOIN exams e ON e.id = es.exam_id
        WHERE u.role = 'siswa'
          AND {$kelasWhere}
          AND es.status = 'submitted'
        GROUP BY e.id, e.title, e.exam_type, e.category
        ORDER BY e.title ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();

    echo json_encode([
        'success'     => true,
        'category'    => $category,
        'total_exams' => count($data),
        'data'        => $data,
    ]);
}

/**
 * GET /api/scores/{category}?title=...
 */
function handleScoresByCategory(string $category, string $title): void {
    $db = getDB();

    $stmtExam = $db->prepare("SELECT id, title, exam_type FROM exams WHERE title = ? LIMIT 1");
    $stmtExam->execute([$title]);
    $exam = $stmtExam->fetch();

    if (!$exam) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Ujian '{$title}' tidak ditemukan."]);
        return;
    }

    $where  = ["es.status = 'submitted'", "es.exam_id = ?", "u.role = 'siswa'"];
    $params = [$exam['id']];

    if ($category === 'calon-guru') {
        $where[] = "u.kelas = 'CALON-GURU'";
    } elseif ($category === 'ppdb') {
        $where[] = "u.kelas = 'PPDB-REGULER'";
    } else {
        $where[] = "u.kelas NOT IN ('CALON-GURU', 'PPDB-REGULER')";
    }

    $whereSQL = implode(' AND ', $where);

    $page   = max(1, (int)($_GET['page']  ?? 1));
    $limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM exam_sessions es JOIN users u ON u.id = es.user_id WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT
            es.id              AS session_id,
            es.score,
            es.is_best_score,
            es.started_at,
            es.submitted_at,
            es.violation_count,
            u.id               AS user_id,
            u.full_name,
            u.username,
            u.kelas
        FROM exam_sessions es
        JOIN users u ON u.id = es.user_id
        WHERE $whereSQL
        ORDER BY es.score DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'exam'     => $exam,
        'category' => $category,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
        ],
        'data' => $data,
    ]);
}

/**
 * GET /api/scores/{user_id}
 */
function handleScoresByUser(int $userId): void {
    $db = getDB();

    $roles        = ALLOWED_ROLES;
    $placeholders = implode(',', array_fill(0, count($roles), '?'));

    $stmt = $db->prepare("SELECT id, full_name, username, role FROM users WHERE id = ? AND role IN ($placeholders)");
    $stmt->execute(array_merge([$userId], $roles));
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User tidak ditemukan atau bukan guru/staff.']);
        return;
    }

    $stmt = $db->prepare("
        SELECT
            es.id            AS session_id,
            es.exam_id,
            es.score,
            es.is_best_score,
            es.started_at,
            es.submitted_at,
            es.violation_count,
            es.status
        FROM exam_sessions es
        WHERE es.user_id = ? AND es.status = 'submitted'
        ORDER BY es.submitted_at DESC
    ");
    $stmt->execute([$userId]);
    $sessions = $stmt->fetchAll();

    echo json_encode([
        'success'        => true,
        'user'           => $user,
        'total_sessions' => count($sessions),
        'data'           => $sessions,
    ]);
}

/**
 * GET /api/scores/{category}/{user_id}
 * Semua ujian yang sudah diikuti oleh user tertentu dalam kategori
 */
function handleScoresByUserAndCategory(string $category, int $userId): void {
    $db = getDB();

    // Tentukan filter kelas
    if ($category === 'calon-guru') {
        $kelasWhere = "u.kelas = 'CALON-GURU'";
    } elseif ($category === 'ppdb') {
        $kelasWhere = "u.kelas = 'PPDB-REGULER'";
    } else {
        $kelasWhere = "u.kelas NOT IN ('CALON-GURU', 'PPDB-REGULER')";
    }

    // Cek user ada dan sesuai kategori
    $stmtUser = $db->prepare("SELECT id, full_name, username, kelas, role FROM users WHERE id = ? AND role = 'siswa' AND {$kelasWhere}");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error'   => "User tidak ditemukan atau tidak termasuk kategori '{$category}'.",
        ]);
        return;
    }

    $stmt = $db->prepare("
        SELECT
            es.id              AS session_id,
            e.id               AS exam_id,
            e.title            AS exam_title,
            e.exam_type,
            es.score,
            es.is_best_score,
            es.started_at,
            es.submitted_at,
            es.violation_count,
            es.status
        FROM exam_sessions es
        JOIN exams e ON e.id = es.exam_id
        WHERE es.user_id = ? AND es.status = 'submitted'
        ORDER BY es.submitted_at DESC
    ");
    $stmt->execute([$userId]);
    $sessions = $stmt->fetchAll();

    echo json_encode([
        'success'        => true,
        'category'       => $category,
        'user'           => $user,
        'total_sessions' => count($sessions),
        'data'           => $sessions,
    ]);
}