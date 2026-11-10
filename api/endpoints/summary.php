<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api.php';

/**
 * GET /api/scores/summary
 * Query params opsional:
 *   - role=guru|staff
 *   - exam_id=1
 */
function handleScoresSummary(): void {
    $db = getDB();

    $role   = $_GET['role']    ?? null;
    $examId = $_GET['exam_id'] ?? null;

    if ($role && !in_array($role, ALLOWED_ROLES, true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'role tidak valid. Gunakan: guru atau staff',
        ]);
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

    $whereSQL = implode(' AND ', $where);

    $stmt = $db->prepare("
        SELECT
            u.id                        AS user_id,
            u.full_name,
            u.username,
            u.role,
            COUNT(es.id)                AS total_attempts,
            MAX(es.score)               AS best_score,
            MIN(es.score)               AS lowest_score,
            ROUND(AVG(es.score), 2)     AS avg_score,
            MAX(es.submitted_at)        AS last_attempt_at
        FROM exam_sessions es
        JOIN users u ON u.id = es.user_id
        WHERE $whereSQL
        GROUP BY u.id, u.full_name, u.username, u.role
        ORDER BY best_score DESC
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success'     => true,
        'total_users' => count($data),
        'data'        => $data,
    ]);
}
