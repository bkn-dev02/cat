<?php

namespace App\Controllers;

class QuestionController
{
    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Pastikan soal dengan $id ini milik user yang login (atau user adalah admin).
     * Throw exception jika tidak diizinkan.
     */
    private function authorizeQuestion(int $id): array
    {
        $user = auth();
        $stmt = db()->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $question = $stmt->fetch();

        if (!$question) {
            throw new \RuntimeException('Soal tidak ditemukan.');
        }

        if ($user['role'] !== 'admin' && (int)$question['created_by'] !== (int)$user['id']) {
            throw new \RuntimeException('Anda tidak memiliki akses ke soal ini.');
        }

        return $question;
    }

    /**
     * Ekstrak field correct_answer dan option_scores dari $_POST berdasarkan tipe soal.
     * Menghilangkan duplikasi di create(), edit(), createInGroup().
     */
    private function extractAnswerFields(string $type): array
    {
        $correct_answer = null;
        $option_scores  = null;

        if (in_array($type, ['multiple_choice', 'listening'], true)) {
            $correct_answer = $_POST['correct_answer'] ?? null;
        } elseif ($type === 'short_answer') {
            $correct_answer = $_POST['short_answer_correct'] ?? null;
        } elseif ($type === 'short_answer_text') {
            $correct_answer = !empty($_POST['correct_answer_text'])
                ? trim($_POST['correct_answer_text'])
                : null;
        } elseif ($type === 'tkp') {
            $scores = [
                'a' => (int)($_POST['option_a_score'] ?? 0),
                'b' => (int)($_POST['option_b_score'] ?? 0),
                'c' => (int)($_POST['option_c_score'] ?? 0),
                'd' => (int)($_POST['option_d_score'] ?? 0),
                'e' => (int)($_POST['option_e_score'] ?? 0),
            ];
            $option_scores  = json_encode($scores);
            $correct_answer = null;
        }

        return [$correct_answer, $option_scores];
    }

    /**
     * Kumpulkan data pilihan jawaban (option_a … option_e) dari $_POST,
     * decode base64 dan sanitasi HTML sekaligus.
     */
    private function extractOptions(): array
    {
        $keys = ['option_a', 'option_b', 'option_c', 'option_d', 'option_e'];
        $out  = [];
        foreach ($keys as $k) {
            $out[] = $this->sanitizeHtml($this->decodeField($_POST[$k] ?? ''));
        }
        return $out;
    }

    /**
     * Decode field yang di-encode base64 dari JS.
     * Jika bukan base64 valid, kembalikan nilai asli.
     */
    private function decodeField(string $value): string
    {
        if (empty($value)) return '';
        $decoded = base64_decode($value, true);
        if ($decoded !== false && mb_check_encoding($decoded, 'UTF-8')) {
            return $decoded;
        }
        return $value;
    }

    /**
     * Sanitasi HTML — izinkan tag & atribut formatting saja.
     * Buang tag berbahaya (script, iframe, dll) tapi pertahankan
     * span[style], b, i, u, sub, sup agar ukuran font & formatting tersimpan.
     */
    private function sanitizeHtml(string $html): string
    {
        if (empty(trim($html))) return '';

        if (strip_tags($html) === $html) {
            return html_entity_decode(htmlspecialchars($html, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        }

        $allowedTags  = ['b','i','u','strong','em','span','br','p','sub','sup','ul','ol','li','div'];
        $allowedAttrs = ['style', 'class'];
        $allowedStyles = [
            'font-size','font-weight','font-style','text-decoration',
            'color','background-color','font-family','line-height',
        ];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<html><head><meta charset="UTF-8"></head><body><div id="wrapper">' . $html . '</div></body></html>'
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Hapus tag tidak diizinkan, pertahankan teks di dalamnya
        foreach (iterator_to_array($xpath->query('//*')) as $node) {
            if (!in_array(strtolower($node->nodeName), $allowedTags, true)) {
                $parent = $node->parentNode;
                if ($parent) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                }
            }
        }

        // Hapus atribut tidak diizinkan & filter style berbahaya
        foreach (iterator_to_array($xpath->query('//@*')) as $attr) {
            $attrName = $attr->nodeName;
            if (!in_array($attrName, $allowedAttrs, true)) {
                $attr->ownerElement->removeAttribute($attrName);
                continue;
            }
            if ($attrName === 'style') {
                $safeParts = [];
                foreach (explode(';', $attr->value) as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    $colonPos = strpos($part, ':');
                    if ($colonPos === false) continue;
                    $prop = trim(strtolower(substr($part, 0, $colonPos)));
                    $val  = trim(substr($part, $colonPos + 1));
                    if (stripos($val, 'javascript') !== false) continue;
                    if (stripos($val, 'expression')  !== false) continue;
                    if (in_array($prop, $allowedStyles, true)) {
                        $safeParts[] = $prop . ': ' . $val;
                    }
                }
                if (empty($safeParts)) {
                    $attr->ownerElement->removeAttribute('style');
                } else {
                    $attr->value = implode('; ', $safeParts);
                }
            }
        }

        $wrapper = $xpath->query('//div[@id="wrapper"]')->item(0);
        if (!$wrapper) return $html;

        $result = '';
        foreach ($wrapper->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim(html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Upload gambar soal.
     * BUG FIX: validasi MIME via finfo, bukan dari header browser yang bisa dimanipulasi.
     */
    private function uploadImage(array $file): string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowed, true)) {
            throw new \RuntimeException('Format gambar tidak didukung');
        }

        $extMap    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $extension = $extMap[$mimeType];
        $filename  = 'q_' . time() . uniqid() . '.' . $extension;

        $uploadDir = 'uploads/questions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            throw new \RuntimeException('Gagal upload gambar');
        }

        return $filename;
    }

    private function uploadAudio(array $file): string
    {
        $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['mp3', 'wav', 'ogg', 'm4a'];

        if (!in_array($ext, $allowedExt, true)) {
            throw new \RuntimeException('Format audio tidak didukung. Gunakan MP3, WAV, OGG, atau M4A.');
        }
        if ($file['size'] > 20 * 1024 * 1024) {
            throw new \RuntimeException('Ukuran file audio maksimal 20MB.');
        }

        $uploadDir = 'uploads/audio/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'audio_' . time() . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            throw new \RuntimeException('Gagal upload file audio.');
        }

        return $filename;
    }

    /**
     * Simpan gambar dari data base64. Menghilangkan duplikasi di ocrSave() dan ocrSaveBatch().
     * Kembalikan nama file, atau null jika data kosong/gagal.
     */
    private function saveBase64Image(?string $imageData): ?string
    {
        if (!$imageData || strpos($imageData, 'data:image') !== 0) {
            return null;
        }

        if (strlen($imageData) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Ukuran gambar terlalu besar (max 5MB)');
        }

        [, $imageData] = explode(';', $imageData);
        [, $imageData] = explode(',', $imageData);
        $binary = base64_decode($imageData);

        if ($binary === false) {
            throw new \RuntimeException('Gagal decode gambar');
        }

        $uploadDir = 'uploads/questions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'q_' . time() . uniqid() . '.png';
        if (!file_put_contents($uploadDir . $filename, $binary)) {
            throw new \RuntimeException('Gagal menyimpan gambar');
        }

        return $filename;
    }

    /**
     * Recalculate scores untuk semua exam_sessions yang memakai soal ini.
     */
    private function recalculateScoresForQuestion(int $questionId): void
    {
        try {
            db()->prepare("
                UPDATE exam_sessions es
                JOIN (
                    SELECT
                        es2.id,
                        COALESCE(SUM(
                            CASE
                                WHEN q.type = 'essay' THEN 0

                                WHEN q.type = 'tkp' THEN COALESCE(
                                    CAST(JSON_UNQUOTE(JSON_EXTRACT(
                                        q.option_scores,
                                        CONCAT('$.', LOWER(TRIM(a.answer)))
                                    )) AS DECIMAL(20,6)),
                                    CAST(JSON_UNQUOTE(JSON_EXTRACT(
                                        q.option_scores,
                                        CONCAT('$.', UPPER(TRIM(a.answer)))
                                    )) AS DECIMAL(20,6)),
                                    0
                                )

                                WHEN q.type = 'short_answer'
                                     AND a.answer REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                                     AND q.correct_answer REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                                     AND CAST(a.answer AS DECIMAL(20,6)) = CAST(q.correct_answer AS DECIMAL(20,6))
                                    THEN COALESCE(q.points, 1)

                                WHEN q.type IN ('multiple_choice', 'listening', 'short_answer_text')
                                     AND LOWER(TRIM(a.answer)) = LOWER(TRIM(q.correct_answer))
                                    THEN COALESCE(q.points, 1)

                                ELSE 0
                            END
                        ), 0) AS nilai_baru
                    FROM exam_sessions es2
                    JOIN (
                        SELECT DISTINCT exam_id
                        FROM exam_questions
                        WHERE question_id = ?
                    ) changed_exam ON changed_exam.exam_id = es2.exam_id
                    JOIN exam_questions eq ON eq.exam_id = es2.exam_id
                    JOIN questions q ON q.id = eq.question_id
                    LEFT JOIN answers a
                        ON a.session_id = es2.id
                        AND a.question_id = q.id
                    WHERE es2.status IN ('submitted','graded','completed')
                    GROUP BY es2.id
                ) calculated ON calculated.id = es.id
                SET es.score = calculated.nilai_baru
            ")->execute([$questionId]);
        } catch (\Exception $e) {
            error_log("recalculateScoresForQuestion error: " . $e->getMessage());
        }
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    private function getQuestionsData(): array
    {
        $user    = auth();
        $perPage = 50;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;
        $search  = trim($_GET['search'] ?? '');
        $type    = trim($_GET['type'] ?? '');
        $subject = trim($_GET['subject'] ?? '');

        $where  = [];
        $params = [];

        if ($user['role'] !== 'admin') {
            $where[]  = 'q.created_by = ?';
            $params[] = $user['id'];
        }
        if ($search !== '') {
            $where[]  = '(q.question_text LIKE ? OR s.name LIKE ? OR u.full_name LIKE ? OR q.sub_topic LIKE ?)';
            $like     = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($type !== '') {
            $where[]  = 'q.type = ?';
            $params[] = $type;
        }
        if ($subject !== '') {
            $where[]  = 'LOWER(s.name) = ?';
            $params[] = strtolower($subject);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = db()->prepare("
            SELECT COUNT(*) FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            JOIN users u ON q.created_by = u.id
            $whereSql
        ");
        $countStmt->execute($params);
        $totalRows  = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRows / $perPage));

        $stmt = db()->prepare("
            SELECT q.id, q.type, q.question_text, q.points, q.created_at,
                   q.pdf_file, q.sub_topic, q.question_image, q.correct_answer,
                   s.name as subject_name, u.full_name as creator_name
            FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            JOIN users u ON q.created_by = u.id
            $whereSql
            ORDER BY q.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $questions = $stmt->fetchAll();

        $statsMap = [];
        $ids = array_column($questions, 'id');
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $statsStmt = db()->prepare("
                    SELECT
                        eq.question_id,
                        eq.dipakai_di,
                        COALESCE(answer_stats.total_jawaban, 0) as total_jawaban,
                        COALESCE(answer_stats.total_benar, 0) as total_benar
                    FROM (
                        SELECT question_id, COUNT(DISTINCT exam_id) as dipakai_di
                        FROM exam_questions
                        WHERE question_id IN ($placeholders)
                        GROUP BY question_id
                    ) eq
                    JOIN questions q ON q.id = eq.question_id
                    LEFT JOIN (
                        SELECT
                            a.question_id,
                            COUNT(*) as total_jawaban,
                            SUM(CASE WHEN LOWER(TRIM(a.answer)) = LOWER(TRIM(q_stats.correct_answer)) THEN 1 ELSE 0 END) as total_benar
                        FROM answers a
                        JOIN questions q_stats ON q_stats.id = a.question_id
                        WHERE a.question_id IN ($placeholders)
                        GROUP BY a.question_id
                    ) answer_stats ON answer_stats.question_id = eq.question_id
                    WHERE q.type != 'essay'
                ");
                $statsStmt->execute(array_merge($ids, $ids));
                foreach ($statsStmt->fetchAll() as $st) {
                    $statsMap[$st['question_id']] = $st;
                }
            } catch (\Exception $e) {
                error_log("Stats query error: " . $e->getMessage());
            }
        }

        $subjectOptions = db()->query("SELECT DISTINCT name FROM subjects ORDER BY name ASC")
            ->fetchAll(\PDO::FETCH_COLUMN);

        return compact('questions', 'statsMap', 'page', 'totalPages', 'totalRows', 'search', 'type', 'subject', 'subjectOptions');
    }

    public function index()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $data = $this->getQuestionsData();

        if (!empty($_GET['ajax'])) {
            view('questions/_table', $data);
            return;
        }

        view('questions/index', $data);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            set_time_limit(60);
            if (ob_get_level()) ob_end_clean();

            try {
                $subject_id       = $_POST['subject_id'] ?? '';
                $type             = $_POST['type'] ?? '';
                $points           = $_POST['points'] ?? 1;
                $action           = $_POST['action'] ?? 'save';
                $sub_topic        = !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null;
                $difficulty_level = !empty($_POST['difficulty_level']) ? $_POST['difficulty_level'] : null;

                $question_text = $this->sanitizeHtml($this->decodeField($_POST['question_text'] ?? ''));

                $question_image = null;
                if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
                    $question_image = $this->uploadImage($_FILES['question_image']);
                }

                $audio_file = null;
                $max_plays  = null;
                if ($type === 'listening') {
                    $max_plays = 2;
                    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                        $audio_file = $this->uploadAudio($_FILES['audio_file']);
                    }
                }

                $hide_correct_answer    = isset($_POST['hide_correct_answer']) ? 1 : 0;
                [$correct_answer, $option_scores] = $this->extractAnswerFields($type);
                $options                = $this->extractOptions();

                $stmt = db()->prepare("
                    INSERT INTO questions (
                        subject_id, type, sub_topic, difficulty_level, question_text, question_image,
                        audio_file, max_plays,
                        option_a, option_b, option_c, option_d, option_e,
                        correct_answer, option_scores, hide_correct_answer, points, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $subject_id, $type, $sub_topic, $difficulty_level, $question_text, $question_image,
                    $audio_file, $max_plays,
                    ...$options,
                    $correct_answer, $option_scores, $hide_correct_answer, $points, auth()['id'],
                ]);

                $_SESSION['success']         = 'Soal berhasil ditambahkan!';
                $_SESSION['question_count']  = ($_SESSION['question_count'] ?? 0) + 1;
                $_SESSION['last_subject_id'] = $subject_id;

                $exam_id = $_POST['exam_id'] ?? null;

                if ($action === 'save_and_add') {
                    $qs = $exam_id ? "?subject_id={$subject_id}&exam_id={$exam_id}" : "?subject_id={$subject_id}";
                    redirect('question/create' . $qs);
                } else {
                    unset($_SESSION['question_count'], $_SESSION['last_subject_id']);
                    redirect($exam_id ? 'exam/assignQuestions/' . $exam_id : 'question');
                }
            } catch (\Exception $e) {
                error_log("Error creating question: " . $e->getMessage());
                $_SESSION['error'] = 'Gagal menyimpan soal: ' . $e->getMessage();
                redirect('question/create');
            }
        }

        $preSelectedSubjectId = $_GET['subject_id'] ?? null;
        if ($preSelectedSubjectId) {
            $_SESSION['last_subject_id'] = $preSelectedSubjectId;
        }

        view('questions/create', [
            'subjects'      => db()->query("SELECT * FROM subjects ORDER BY name")->fetchAll(),
            'lastSubjectId' => $preSelectedSubjectId ?? ($_SESSION['last_subject_id'] ?? null),
            'questionCount' => $_SESSION['question_count'] ?? 0,
            'examId'        => $_GET['exam_id'] ?? null,
        ]);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            set_time_limit(60);

            try {
                // BUG FIX #1: verifikasi kepemilikan sebelum update
                $this->authorizeQuestion((int)$id);

                $action = $_POST['action'] ?? 'update';
                $type   = $_POST['type'] ?? 'multiple_choice';

                // Handle gambar
                $question_image = $_POST['existing_image'] ?? null;
                if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
                    if ($question_image && file_exists('uploads/questions/' . $question_image)) {
                        unlink('uploads/questions/' . $question_image);
                    }
                    $question_image = $this->uploadImage($_FILES['question_image']);
                }
                if (!empty($_POST['delete_image'])) {
                    if ($question_image && file_exists('uploads/questions/' . $question_image)) {
                        unlink('uploads/questions/' . $question_image);
                    }
                    $question_image = null;
                }

                // Handle audio
                $audio_file = $_POST['existing_audio'] ?? null;
                $max_plays  = null;
                if ($type === 'listening') {
                    $max_plays = (int)($_POST['max_plays'] ?? 2);
                    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                        if ($audio_file && file_exists('uploads/audio/' . $audio_file)) {
                            unlink('uploads/audio/' . $audio_file);
                        }
                        $audio_file = $this->uploadAudio($_FILES['audio_file']);
                    }
                    if (!empty($_POST['delete_audio'])) {
                        if ($audio_file && file_exists('uploads/audio/' . $audio_file)) {
                            unlink('uploads/audio/' . $audio_file);
                        }
                        $audio_file = null;
                    }
                }

                $sub_topic           = !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null;
                $difficulty_level    = !empty($_POST['difficulty_level']) ? $_POST['difficulty_level'] : null;
                $hide_correct_answer = isset($_POST['hide_correct_answer']) ? 1 : 0;

                [$correct_answer, $option_scores] = $this->extractAnswerFields($type);
                $options = $this->extractOptions();

                $stmt = db()->prepare("
                    UPDATE questions
                    SET subject_id = ?, type = ?, sub_topic = ?, difficulty_level = ?,
                        question_text = ?, question_image = ?,
                        audio_file = ?, max_plays = ?,
                        option_a = ?, option_b = ?, option_c = ?, option_d = ?, option_e = ?,
                        correct_answer = ?, option_scores = ?, hide_correct_answer = ?, points = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['subject_id'], $type, $sub_topic, $difficulty_level,
                    $this->sanitizeHtml($this->decodeField($_POST['question_text'] ?? '')),
                    $question_image, $audio_file, $max_plays,
                    ...$options,
                    $correct_answer, $option_scores, $hide_correct_answer, $_POST['points'],
                    $id,
                ]);

                $this->recalculateScoresForQuestion((int)$id);

                $_SESSION['success'] = 'Soal berhasil diupdate!';

                if ($action === 'update_and_add') {
                    $_SESSION['last_subject_id'] = $_POST['subject_id'];
                    redirect('question/create?subject_id=' . $_POST['subject_id']);
                } else {
                    redirect('question');
                }
            } catch (\Exception $e) {
                error_log("Error updating question: " . $e->getMessage());
                $_SESSION['error'] = 'Gagal update soal: ' . $e->getMessage();
                redirect('question/edit/' . $id);
            }
        }

        try {
            $question = $this->authorizeQuestion((int)$id);
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect('question');
            return;
        }

        view('questions/edit', [
            'question' => $question,
            'subjects' => db()->query("SELECT * FROM subjects ORDER BY name")->fetchAll(),
        ]);
    }

    // =========================================================================
    // PREVIEW
    // =========================================================================

    public function previewData($id)
    {
        while (ob_get_level()) ob_end_clean();
        requireAuth();
        requireRole(['admin', 'guru']);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $stmt = db()->prepare("
                SELECT q.*, s.name as subject_name
                FROM questions q
                JOIN subjects s ON q.subject_id = s.id
                WHERE q.id = ?
            ");
            $stmt->execute([(int)$id]);
            $question = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$question) {
                echo json_encode(['success' => false, 'message' => 'Soal tidak ditemukan']);
                exit;
            }

            echo json_encode(['success' => true, 'question' => $question], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // =========================================================================
    // DUPLICATE
    // =========================================================================

    public function duplicate($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        try {
            // BUG FIX #1: verifikasi kepemilikan sebelum duplikasi
            $q = $this->authorizeQuestion((int)$id);
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect('question');
            return;
        }

        $stmt = db()->prepare("
            INSERT INTO questions (
                group_id, subject_id, type, sub_topic, difficulty_level,
                question_text, question_image, audio_file, max_plays,
                option_a, option_b, option_c, option_d, option_e,
                correct_answer, option_scores, hide_correct_answer, points,
                created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $q['group_id'] ?? null,
            $q['subject_id'],
            $q['type'],
            $q['sub_topic'],
            $q['difficulty_level'] ?? null,
            $q['question_text'],
            $q['question_image'],
            $q['audio_file'],
            $q['max_plays'] ?? null,
            $q['option_a'],
            $q['option_b'],
            $q['option_c'],
            $q['option_d'],
            $q['option_e'],
            $q['correct_answer'],
            $q['option_scores'] ?? null,
            $q['hide_correct_answer'] ?? 0,
            $q['points'],
            auth()['id'],
        ]);

        $_SESSION['success'] = 'Soal berhasil diduplikasi!';
        redirect('question');
    }

    // =========================================================================
    // CREATE IN GROUP
    // =========================================================================

    public function createInGroup($groupId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('questionGroup/show/' . $groupId);
            return;
        }

        try {
            set_time_limit(30);

            $subject_id    = $_POST['subject_id'] ?? '';
            $type          = $_POST['type'] ?? 'multiple_choice';
            $sub_topic     = !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null;
            $points        = $_POST['points'] ?? 1;
            $question_text = $this->sanitizeHtml($this->decodeField($_POST['question_text'] ?? ''));

            $question_image = null;
            if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
                $question_image = $this->uploadImage($_FILES['question_image']);
            }

            $audio_file = null;
            $max_plays  = null;
            if ($type === 'listening') {
                $max_plays = 2;
                if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                    $audio_file = $this->uploadAudio($_FILES['audio_file']);
                }
            }

            $hide_correct_answer              = isset($_POST['hide_correct_answer']) ? 1 : 0;
            [$correct_answer, $option_scores] = $this->extractAnswerFields($type);

            // TKP: poin otomatis dari skor tertinggi
            if ($type === 'tkp') {
                $tkpScores = json_decode($option_scores, true);
                $points    = max(1, max($tkpScores));
            }

            $options = $this->extractOptions();

            $stmt = db()->prepare("
                INSERT INTO questions (
                    group_id, subject_id, type, sub_topic, question_text, question_image,
                    audio_file, max_plays,
                    option_a, option_b, option_c, option_d, option_e,
                    correct_answer, option_scores, hide_correct_answer, points, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $groupId, $subject_id, $type, $sub_topic, $question_text, $question_image,
                $audio_file, $max_plays,
                ...$options,
                $correct_answer, $option_scores, $hide_correct_answer, $points, auth()['id'],
            ]);

            $_SESSION['success'] = 'Soal berhasil ditambahkan ke grup!';
            redirect('questionGroup/show/' . $groupId);
        } catch (\Exception $e) {
            error_log("Error createInGroup: " . $e->getMessage());
            $_SESSION['error'] = 'Gagal menyimpan soal: ' . $e->getMessage();
            redirect('questionGroup/show/' . $groupId);
        }
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $isAjax = (
            $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])
        ) || (
            isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        try {
            // BUG FIX #1: verifikasi kepemilikan sebelum hapus
            $question = $this->authorizeQuestion((int)$id);
        } catch (\RuntimeException $e) {
            if ($isAjax) {
                while (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            $_SESSION['error'] = $e->getMessage();
            redirect('question');
            return;
        }

        // Hapus file terkait
        if ($question['question_image']) {
            $path = 'uploads/questions/' . $question['question_image'];
            if (file_exists($path)) unlink($path);
        }
        if ($question['audio_file']) {
            $path = 'uploads/audio/' . $question['audio_file'];
            if (file_exists($path)) unlink($path);
        }

        db()->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);

        if ($isAjax) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Soal berhasil dihapus!']);
            exit;
        }

        $_SESSION['success'] = 'Soal berhasil dihapus!';
        redirect('question');
    }

    // =========================================================================
    // PDF UPLOAD & VIEW
    // =========================================================================

    public function uploadPdfStep1()
    {
        while (ob_get_level()) ob_end_clean();
        requireAuth();
        requireRole(['admin', 'guru']);
        header('Content-Type: application/json');

        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload']);
            exit;
        }

        $file = $_FILES['pdf_file'];

        if ($file['type'] !== 'application/pdf') {
            echo json_encode(['success' => false, 'message' => 'Hanya file PDF yang diperbolehkan']);
            exit;
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 10MB']);
            exit;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/pdfs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename    = 'soal_' . time() . '_' . uniqid() . '.pdf';
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
            exit;
        }

        echo json_encode([
            'success'   => true,
            'message'   => 'PDF berhasil diupload',
            'file_path' => '/public/uploads/pdfs/' . $filename,
            'file_name' => $file['name'],
        ]);
        exit;
    }

    public function previewPdfQuestions()
    {
        while (ob_get_level()) ob_end_clean();
        requireAuth();
        requireRole(['admin', 'guru']);
        header('Content-Type: application/json; charset=utf-8');

        $pdfPath       = $_POST['pdf_path'] ?? '';
        $subjectId     = (int)($_POST['subject_id'] ?? 0);
        $subTopic      = !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null;
        $points        = (int)($_POST['points'] ?? 1);
        $questionCount = (int)($_POST['question_count'] ?? 0);
        $answers       = $_POST['answers'] ?? '[]';

        $type = $_POST['type'] ?? 'multiple_choice';
        if (!in_array($type, ['multiple_choice', 'short_answer'], true)) {
            $type = 'multiple_choice';
        }

        if (!$pdfPath) {
            echo json_encode(['success' => false, 'message' => 'Path PDF kosong']);
            exit;
        }
        if (!$subjectId) {
            echo json_encode(['success' => false, 'message' => 'Mata pelajaran tidak dipilih']);
            exit;
        }
        if ($questionCount < 1) {
            echo json_encode(['success' => false, 'message' => 'Jumlah soal tidak valid']);
            exit;
        }

        // BUG FIX #5: validasi bahwa path hasil resolusi berada di dalam direktori yang diizinkan
        $allowedDir = realpath(__DIR__ . '/../../public/uploads/pdfs');
        $fullPath   = realpath(__DIR__ . '/../..' . $pdfPath);

        if (!$fullPath || !$allowedDir || strpos($fullPath, $allowedDir) !== 0 || !file_exists($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'File PDF tidak ditemukan atau tidak diizinkan']);
            exit;
        }

        try {
            $userId     = auth()['id'];
            $fileName   = basename($pdfPath);
            $savedCount = 0;

            // Decode answers JSON dari JS
            $answersArray = json_decode($answers, true);
            if (!is_array($answersArray)) {
                $answersArray = [];
            }

            // ── DIAGNOSTIC LOG ──────────────────────────────────────────────
            error_log("[previewPdfQuestions] user={$userId} type={$type} questionCount={$questionCount}");
            error_log("[previewPdfQuestions] raw answers payload: " . $answers);
            error_log("[previewPdfQuestions] decoded answers keys: " . implode(',', array_keys($answersArray)));
            error_log("[previewPdfQuestions] decoded answers values: " . json_encode(array_values($answersArray)));
            // ────────────────────────────────────────────────────────────────

            // BUG FIX: JSON.stringify dari JS menghasilkan key string ("1","2","3"),
            // sedangkan loop PHP memakai integer. Normalisasi semua key ke integer
            // agar $answersArray[1] selalu cocok dengan $i = 1.
            $normalizedAnswers = [];
            foreach ($answersArray as $key => $val) {
                $intKey = (int)$key;
                if ($intKey > 0) {
                    $normalizedAnswers[$intKey] = $val;
                }
            }

            error_log("[previewPdfQuestions] normalized keys: " . implode(',', array_keys($normalizedAnswers)));

            $stmt = db()->prepare("
                INSERT INTO questions (
                    subject_id, type, sub_topic, question_number,
                    question_text, pdf_file, correct_answer,
                    points, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            for ($i = 1; $i <= $questionCount; $i++) {
                // Akses dengan integer key yang sudah dinormalisasi
                $correctAnswer = $normalizedAnswers[$i] ?? null;

                if ($type === 'short_answer' && $correctAnswer !== null) {
                    $correctAnswer = trim((string)$correctAnswer);
                    if ($correctAnswer === '' || !is_numeric($correctAnswer)) {
                        $correctAnswer = null;
                    }
                }

                // Untuk multiple_choice: pastikan nilainya A/B/C/D/E atau null
                if ($type === 'multiple_choice' && $correctAnswer !== null) {
                    $correctAnswer = strtoupper(trim((string)$correctAnswer));
                    if (!in_array($correctAnswer, ['A','B','C','D','E'], true)) {
                        $correctAnswer = null;
                    }
                }

                error_log("[previewPdfQuestions] soal #{$i} -> correct_answer=" . ($correctAnswer ?? 'NULL'));

                $stmt->execute([
                    $subjectId, $type, $subTopic, $i,
                    'Soal PDF #' . $i . ' - ' . $fileName,
                    $pdfPath, $correctAnswer, $points, $userId,
                ]);
                $savedCount++;
            }

            error_log("[previewPdfQuestions] selesai, savedCount={$savedCount}");

            echo json_encode([
                'success' => true,
                'message' => "Berhasil menyimpan {$savedCount} soal dari PDF",
                'saved'   => $savedCount,
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Tampilkan PDF di browser (inline, bukan download).
     * BUG FIX #3: tambahan validasi direktori agar tidak ada path traversal.
     */
    public function viewPdf()
    {
        while (ob_get_level()) ob_end_clean();
        requireAuth();

        $file = $_GET['file'] ?? '';

        if (empty($file) || pathinfo($file, PATHINFO_EXTENSION) !== 'pdf') {
            http_response_code(400);
            exit('File tidak valid');
        }

        // basename() sudah memotong path traversal, ditambah validasi direktori
        $file       = basename($file);
        $allowedDir = realpath(__DIR__ . '/../../public/uploads/pdfs');
        $filePath   = realpath(__DIR__ . '/../../public/uploads/pdfs/' . $file);

        if (!$filePath || !$allowedDir || strpos($filePath, $allowedDir) !== 0) {
            http_response_code(404);
            exit('File PDF tidak ditemukan');
        }

        if (!is_readable($filePath)) {
            http_response_code(403);
            exit('File tidak dapat dibaca');
        }

        $fileSize = filesize($filePath);

        header('HTTP/1.1 200 OK');
        header('Content-Type: application/pdf; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $fileSize);
        header('Accept-Ranges: bytes');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            http_response_code(500);
            exit('Gagal membuka file');
        }

        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }

        fclose($handle);
        exit;
    }

    // =========================================================================
    // PDF TEXT PARSING (internal)
    // =========================================================================

    private function extractTextFromPdf(string $filePath): string
    {
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $text   = $parser->parseFile($filePath)->getText();
                if (!empty($text)) return $text;
            } catch (\Exception $e) {
                error_log("Smalot parser error: " . $e->getMessage());
            }
        }

        if (function_exists('shell_exec')) {
            $output = shell_exec("pdftotext " . escapeshellarg($filePath) . " - 2>&1");
            if (!empty($output) && strpos($output, 'Error') === false) {
                return $output;
            }
        }

        $content = file_get_contents($filePath);
        return $content !== false ? $this->extractTextFromPdfContent($content) : '';
    }

    private function extractTextFromPdfContent(string $content): string
    {
        $text = preg_replace('/obj.*?endobj/s', '', $content);
        $text = preg_replace('/stream.*?endstream/s', '', $text);
        $text = preg_replace('/<</', ' ', $text);
        $text = preg_replace('/>>/', ' ', $text);
        $text = preg_replace('/\[\]/', ' ', $text);
        $text = preg_replace('/\(\)/', ' ', $text);
        $text = preg_replace_callback('/<([0-9A-Fa-f]+)>/', fn($m) => hex2bin($m[1]), $text);
        $text = preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function parseQuestionsFromText(string $text, int $count): array
    {
        $questions = [];
        $text      = str_replace(["\r\n", "\r"], "\n", $text);

        error_log("Parsing text: " . substr($text, 0, 200));

        $pattern = '/(?=\n?\s*(?:Soal\s*)?(\d+)[\.\)]\s+)/i';
        $parts   = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $currentNumber   = null;
        $currentQuestion = '';
        $currentOptions  = [];
        $currentCorrect  = null;
        $optionPattern   = '/^([A-E])[\.\)]\s*(.+?)(?=\n[A-E][\.\)]|\n\d+[\.\)]|\Z)/s';

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            if (preg_match('/^(?:Soal\s*)?(\d+)[\.\)]\s*(.*)$/s', $part, $matches)) {
                if ($currentNumber !== null && !empty(trim($currentQuestion))) {
                    $questions[] = [
                        'number'         => $currentNumber,
                        'question'       => trim($currentQuestion),
                        'options'        => $currentOptions,
                        'correct_answer' => $currentCorrect,
                    ];
                    if (count($questions) >= $count) break;
                }
                $currentNumber   = (int)$matches[1];
                $currentQuestion = trim($matches[2]);
                $currentOptions  = [];
                $currentCorrect  = null;
                error_log("Found question #{$currentNumber}: " . substr($currentQuestion, 0, 100));
            } elseif (preg_match($optionPattern, $part, $optMatches)) {
                $option     = $optMatches[1];
                $optionText = trim($optMatches[2]);
                $currentOptions[$option] = $optionText;
                if (preg_match('/\*|\(jawaban benar\)|\(kunci\)|✓/i', $optionText)) {
                    $currentCorrect          = $option;
                    $currentOptions[$option] = preg_replace('/\*|\(jawaban benar\)|\(kunci\)|✓/i', '', $optionText);
                }
            } elseif (empty($currentOptions)) {
                $currentQuestion .= "\n" . $part;
            }
        }

        if ($currentNumber !== null && !empty(trim($currentQuestion)) && count($questions) < $count) {
            $questions[] = [
                'number'         => $currentNumber,
                'question'       => trim($currentQuestion),
                'options'        => $currentOptions,
                'correct_answer' => $currentCorrect,
            ];
        }

        error_log("Total questions parsed: " . count($questions));
        return $questions;
    }

    // =========================================================================
    // OCR
    // =========================================================================

    public function ocrSubjects()
    {
        requireAuth();
        requireRole(['admin', 'guru']);
        header('Content-Type: application/json');

        try {
            $subjects = db()->query("SELECT id, name FROM subjects ORDER BY name ASC")
                ->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'subjects' => $subjects]);
        } catch (\Exception $e) {
            error_log('ocrSubjects error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data mata pelajaran: ' . $e->getMessage()]);
        }
        exit;
    }

    public function ocrSave()
    {
        requireAuth();
        requireRole(['admin', 'guru']);  // BUG FIX #7: whitelist, bukan blacklist
        header('Content-Type: application/json');

        $subjectId     = $_POST['subject_id']     ?? null;
        $questionText  = trim($_POST['question_text'] ?? '');
        $optionA       = trim($_POST['option_a'] ?? '');
        $optionB       = trim($_POST['option_b'] ?? '');
        $optionC       = trim($_POST['option_c'] ?? '');
        $optionD       = trim($_POST['option_d'] ?? '');
        $optionE       = trim($_POST['option_e'] ?? '');
        $correctAnswer = strtoupper(trim($_POST['correct_answer'] ?? ''));
        $points        = max(1, (int)($_POST['points'] ?? 1));

        if (!$subjectId) {
            echo json_encode(['success' => false, 'message' => 'Mata pelajaran belum dipilih']);
            exit;
        }
        if ($questionText === '') {
            echo json_encode(['success' => false, 'message' => 'Teks soal kosong']);
            exit;
        }
        if (!in_array($correctAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
            echo json_encode(['success' => false, 'message' => 'Jawaban benar tidak valid']);
            exit;
        }
        $optionsMap = ['A' => $optionA, 'B' => $optionB, 'C' => $optionC, 'D' => $optionD, 'E' => $optionE];
        if ($optionsMap[$correctAnswer] === '') {
            echo json_encode(['success' => false, 'message' => "Opsi $correctAnswer kosong"]);
            exit;
        }

        try {
            // BUG FIX: gunakan helper saveBase64Image(), hapus duplikasi kode
            $questionImage = null;
            try {
                $questionImage = $this->saveBase64Image($_POST['question_image_data'] ?? null);
            } catch (\Exception $e) {
                error_log('Image upload error: ' . $e->getMessage());
            }

            $stmt = db()->prepare("
                INSERT INTO questions
                    (subject_id, type, question_text, question_image,
                     option_a, option_b, option_c, option_d, option_e,
                     correct_answer, points, created_by, created_at)
                VALUES
                    (?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $subjectId, $questionText, $questionImage,
                $optionA ?: null, $optionB ?: null, $optionC ?: null,
                $optionD ?: null, $optionE ?: null,
                $correctAnswer, $points, auth()['id'],
            ]);

            echo json_encode(['success' => true, 'id' => db()->lastInsertId(), 'message' => 'Soal tersimpan']);
        } catch (\Exception $e) {
            error_log('Save question error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Gagal simpan: ' . $e->getMessage()]);
        }
        exit;
    }

    public function ocrSaveBatch()
    {
        requireAuth();
        requireRole(['admin', 'guru']);  // BUG FIX #7: whitelist, bukan blacklist
        header('Content-Type: application/json');

        $questions = json_decode($_POST['questions'] ?? '[]', true);

        if (!is_array($questions) || empty($questions)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada soal yang disimpan']);
            exit;
        }

        $savedCount = 0;
        $errors     = [];
        $userId     = auth()['id'];

        $stmt = db()->prepare("
            INSERT INTO questions
                (subject_id, type, question_text, question_image,
                 option_a, option_b, option_c, option_d, option_e,
                 correct_answer, points, created_by, created_at)
            VALUES
                (?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($questions as $index => $q) {
            try {
                $subjectId     = $q['subject_id'] ?? null;
                $questionText  = trim($q['question_text'] ?? '');
                $correctAnswer = strtoupper(trim($q['correct_answer'] ?? ''));
                $points        = max(1, (int)($q['points'] ?? 1));

                if (!$subjectId || $questionText === '' || !in_array($correctAnswer, ['A','B','C','D','E'], true)) {
                    $errors[] = "Soal #" . ($index + 1) . ": Data tidak valid";
                    continue;
                }

                // BUG FIX: gunakan helper saveBase64Image(), hapus duplikasi kode
                $questionImage = null;
                try {
                    $questionImage = $this->saveBase64Image($q['image_data'] ?? null);
                } catch (\Exception $e) {
                    error_log('Batch image error #' . ($index + 1) . ': ' . $e->getMessage());
                }

                $stmt->execute([
                    $subjectId,
                    $questionText,
                    $questionImage,
                    trim($q['option_a'] ?? '') ?: null,
                    trim($q['option_b'] ?? '') ?: null,
                    trim($q['option_c'] ?? '') ?: null,
                    trim($q['option_d'] ?? '') ?: null,
                    trim($q['option_e'] ?? '') ?: null,
                    $correctAnswer,
                    $points,
                    $userId,
                ]);
                $savedCount++;
            } catch (\Exception $e) {
                $errors[] = "Soal #" . ($index + 1) . ": " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true,
            'saved'   => $savedCount,
            'total'   => count($questions),
            'errors'  => $errors,
            'message' => "Berhasil menyimpan $savedCount dari " . count($questions) . " soal",
        ]);
        exit;
    }
}
