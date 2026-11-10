<?php

namespace App\Controllers;

class QuestionController
{
    private function getQuestionsData(): array
    {
        $user = auth();
    
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
                q.pdf_file, q.question_number, q.sub_topic, q.question_image, q.correct_answer,
                s.name as subject_name, u.full_name as creator_name
            FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            JOIN users u ON q.created_by = u.id
            LEFT JOIN (
                SELECT pdf_file, MAX(id) AS grp_id
                FROM questions
                WHERE pdf_file IS NOT NULL AND pdf_file != ''
                GROUP BY pdf_file
            ) g ON g.pdf_file = q.pdf_file
            $whereSql
            ORDER BY
                COALESCE(g.grp_id, q.id) DESC,          
                (q.question_number IS NULL) ASC,        
                CAST(q.question_number AS UNSIGNED) ASC,
                q.id ASC                                
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $questions = $stmt->fetchAll();
    
        $statsMap = [];
        $ids = array_column($questions, 'id');
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $statsStmt = db()->prepare("
                    SELECT 
                        eq.question_id,
                        COUNT(DISTINCT eq.exam_id) as dipakai_di,
                        COUNT(a.id) as total_jawaban,
                        SUM(CASE WHEN LOWER(TRIM(a.answer)) = LOWER(TRIM(q.correct_answer)) THEN 1 ELSE 0 END) as total_benar
                    FROM exam_questions eq
                    JOIN questions q ON q.id = eq.question_id  
                    LEFT JOIN answers a ON a.question_id = eq.question_id
                    WHERE q.type != 'essay' AND eq.question_id IN ($placeholders)
                    GROUP BY eq.question_id
                ");
                $statsStmt->execute($ids);
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

    public function create()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            set_time_limit(60);
            if (ob_get_level()) ob_end_clean();

            try {
                $subject_id   = $_POST['subject_id'] ?? '';
                $type         = $_POST['type'] ?? '';
                $points       = $_POST['points'] ?? 1;
                $action       = $_POST['action'] ?? 'save';
                $sub_topic    = !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null;
                $difficulty_level = !empty($_POST['difficulty_level']) ? $_POST['difficulty_level'] : null;

                // Decode base64 lalu sanitasi
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

                $hide_correct_answer = isset($_POST['hide_correct_answer']) ? 1 : 0;

                $correct_answer = null;
                if ($type === 'multiple_choice' || $type === 'listening') {
                    $correct_answer = $_POST['correct_answer'] ?? null;
                } elseif ($type === 'short_answer') {
                    $correct_answer = $_POST['short_answer_correct'] ?? null;
                } elseif ($type === 'short_answer_text') {
                    $correct_answer = isset($_POST['correct_answer_text']) ? trim($_POST['correct_answer_text']) : null;
                }
                $option_scores = null;
                if ($type === 'tkp') {
                    $option_scores = json_encode([
                        'a' => (int)($_POST['option_a_score'] ?? 0),
                        'b' => (int)($_POST['option_b_score'] ?? 0),
                        'c' => (int)($_POST['option_c_score'] ?? 0),
                        'd' => (int)($_POST['option_d_score'] ?? 0),
                        'e' => (int)($_POST['option_e_score'] ?? 0),
                    ]);
                    $correct_answer = null; 
                }

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
                    $this->sanitizeHtml($this->decodeField($_POST['option_a'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_b'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_c'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_d'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_e'] ?? '')),
                    $correct_answer, $option_scores, $hide_correct_answer, $points, auth()['id']
                ]);

                $_SESSION['success']        = 'Soal berhasil ditambahkan!';
                $_SESSION['question_count'] = ($_SESSION['question_count'] ?? 0) + 1;
                $_SESSION['last_subject_id'] = $subject_id;

                $exam_id = $_POST['exam_id'] ?? null;

                if ($action === 'save_and_add') {
                    if ($exam_id) {
                        redirect('question/create?subject_id=' . $subject_id . '&exam_id=' . $exam_id);
                    } else {
                        redirect('question/create?subject_id=' . $subject_id);
                    }
                } else {
                    unset($_SESSION['question_count'], $_SESSION['last_subject_id']);
                    if ($exam_id) {
                        redirect('exam/assignQuestions/' . $exam_id);
                    } else {
                        redirect('question');
                    }
                }
            } catch (\Exception $e) {
                error_log("Error creating question: " . $e->getMessage());
                $_SESSION['error'] = 'Gagal menyimpan soal: ' . $e->getMessage();
                redirect('question/create');
            }
        }

        $subjects            = db()->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
        $preSelectedSubjectId = $_GET['subject_id'] ?? null;
        $examId              = $_GET['exam_id'] ?? null;
        $lastSubjectId       = $preSelectedSubjectId ?? ($_SESSION['last_subject_id'] ?? null);
        $questionCount       = $_SESSION['question_count'] ?? 0;

        if ($preSelectedSubjectId) {
            $_SESSION['last_subject_id'] = $preSelectedSubjectId;
        }

        view('questions/create', [
            'subjects'      => $subjects,
            'lastSubjectId' => $lastSubjectId,
            'questionCount' => $questionCount,
            'examId'        => $examId
        ]);
    }

    public function edit($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            set_time_limit(60);

            try {
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

                $correct_answer = null;
                $option_scores  = null;
                if (in_array($type, ['multiple_choice', 'listening'])) {
                    $correct_answer = $_POST['correct_answer'] ?? null;
                } elseif ($type === 'short_answer') {
                    $correct_answer = $_POST['correct_answer_number'] ?? null;
                } elseif ($type === 'short_answer_text') {
                    $correct_answer = !empty($_POST['correct_answer_text'])
                        ? trim($_POST['correct_answer_text']) : null;
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

                // Decode base64 lalu sanitasi
                $question_text = $this->sanitizeHtml($this->decodeField($_POST['question_text'] ?? ''));

                $stmt = db()->prepare("
                    UPDATE questions 
                    SET subject_id = ?, type = ?, sub_topic = ?, difficulty_level = ?, question_text = ?, question_image = ?,
                        audio_file = ?, max_plays = ?,
                        option_a = ?, option_b = ?, option_c = ?, option_d = ?, option_e = ?,
                        correct_answer = ?, option_scores = ?, hide_correct_answer = ?, points = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $_POST['subject_id'], $type, $sub_topic, $difficulty_level, $question_text, $question_image,
                    $audio_file, $max_plays,
                    $this->sanitizeHtml($this->decodeField($_POST['option_a'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_b'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_c'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_d'] ?? '')),
                    $this->sanitizeHtml($this->decodeField($_POST['option_e'] ?? '')),
                    $correct_answer, $option_scores, $hide_correct_answer, $_POST['points'], $id
                ]);

                $this->recalculateScoresForQuestion($id);

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

        $stmt = db()->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $question = $stmt->fetch();

        if (!$question) {
            $_SESSION['error'] = 'Soal tidak ditemukan.';
            redirect('question');
        }

        $subjects = db()->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
        view('questions/edit', ['question' => $question, 'subjects' => $subjects]);
    }

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

            // Debug log
            error_log("Correct answer for question {$id}: " . ($question['correct_answer'] ?? 'NULL'));

            echo json_encode([
                'success' => true,
                'question' => $question
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ============================================
    // DUPLICATE SOAL
    // ============================================
    public function duplicate($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $stmt = db()->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $q = $stmt->fetch();

        if (!$q) {
            $_SESSION['error'] = 'Soal tidak ditemukan.';
            redirect('question');
        }

        $insertStmt = db()->prepare("
            INSERT INTO questions (
                group_id, subject_id, type, sub_topic, difficulty_level,
                question_text, question_image, audio_file, max_plays,
                option_a, option_b, option_c, option_d, option_e,
                correct_answer, option_scores, hide_correct_answer, points,
                created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $insertStmt->execute([
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

    // ============================================
    // CREATE IN GROUP
    // ============================================
    public function createInGroup($groupId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('questionGroup/show/' . $groupId);
        }

        try {
            set_time_limit(30);

            $subject_id = $_POST['subject_id'] ?? '';
            $type       = $_POST['type'] ?? 'multiple_choice';
            $sub_topic  = !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null;
            $points     = $_POST['points'] ?? 1;
            $action     = $_POST['action'] ?? 'save';

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

        $correct_answer = null;
        $option_scores = null;
        if (in_array($type, ['multiple_choice', 'listening'])) {
            $correct_answer = $_POST['correct_answer'] ?? null;
        } elseif ($type === 'short_answer') {
            $correct_answer = $_POST['short_answer_correct'] ?? null;
        } elseif ($type === 'short_answer_text') {
            $correct_answer = !empty($_POST['correct_answer_text']) ? trim($_POST['correct_answer_text']) : null;
        } elseif ($type === 'tkp') {
            $scores = [
                'a' => (int)($_POST['option_a_score'] ?? 0),
                'b' => (int)($_POST['option_b_score'] ?? 0),
                'c' => (int)($_POST['option_c_score'] ?? 0),
                'd' => (int)($_POST['option_d_score'] ?? 0),
                'e' => (int)($_POST['option_e_score'] ?? 0),
            ];
            $option_scores = json_encode($scores);
            $correct_answer = null;
            $points = max(1, max($scores)); // poin otomatis dari skor tertinggi
        }
        
        $hide_correct_answer = isset($_POST['hide_correct_answer']) ? 1 : 0;
        
        $stmt = db()->prepare("
            INSERT INTO questions (
                group_id, subject_id, type, sub_topic, question_text, question_image,
                audio_file, max_plays,
                option_a, option_b, option_c, option_d, option_e,
                correct_answer, option_scores, hide_correct_answer, points, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $groupId,
            $subject_id,
            $type,
            $sub_topic,
            $question_text,
            $question_image,
            $audio_file,
            $max_plays,
            $this->sanitizeHtml($this->decodeField($_POST['option_a'] ?? '')),
            $this->sanitizeHtml($this->decodeField($_POST['option_b'] ?? '')),
            $this->sanitizeHtml($this->decodeField($_POST['option_c'] ?? '')),
            $this->sanitizeHtml($this->decodeField($_POST['option_d'] ?? '')),
            $this->sanitizeHtml($this->decodeField($_POST['option_e'] ?? '')),
            $correct_answer,
            $option_scores,
            $hide_correct_answer,
            $points,
            auth()['id']
        ]);

            $_SESSION['success'] = 'Soal berhasil ditambahkan ke grup!';
            redirect('questionGroup/show/' . $groupId);
        } catch (\Exception $e) {
            error_log("Error createInGroup: " . $e->getMessage());
            $_SESSION['error'] = 'Gagal menyimpan soal: ' . $e->getMessage();
            redirect('questionGroup/show/' . $groupId);
        }
    }

    public function delete($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        // Deteksi apakah ini dipanggil via AJAX (mis. tombol "Hapus Terpilih" di Bank Soal,
        // yang mengirim POST dengan confirm_delete=1 dan mengharapkan response JSON).
        // Link "Hapus" biasa per-baris tetap request GET, jadi perilakunya tidak berubah
        // (tetap redirect + flash session seperti semula).
        $isAjax = (
            $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])
        ) || (
            isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        $stmt = db()->prepare("SELECT question_image, audio_file FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $question = $stmt->fetch();

        if (!$question) {
            if ($isAjax) {
                while (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Soal tidak ditemukan']);
                exit;
            }
            $_SESSION['error'] = 'Soal tidak ditemukan.';
            redirect('question');
        }

        if ($question['question_image']) {
            $imagePath = 'uploads/questions/' . $question['question_image'];
            if (file_exists($imagePath)) unlink($imagePath);
        }
        if ($question['audio_file']) {
            $audioPath = 'uploads/audio/' . $question['audio_file'];
            if (file_exists($audioPath)) unlink($audioPath);
        }

        $stmt = db()->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);

        if ($isAjax) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Soal berhasil dihapus!']);
            exit;
        }

        $_SESSION['success'] = 'Soal berhasil dihapus!';
        redirect('question');
    }

    // ============================================
    // PRIVATE HELPERS
    // ============================================

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

        // Kalau tidak ada tag HTML sama sekali, kembalikan langsung
        if (strip_tags($html) === $html) {
            return html_entity_decode(htmlspecialchars($html, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        }

        $allowedTags = [
            'b',
            'i',
            'u',
            'strong',
            'em',
            'span',
            'br',
            'p',
            'sub',
            'sup',
            'ul',
            'ol',
            'li',
            'div'
        ];

        $allowedAttrs = ['style', 'class'];

        // Atribut style yang diizinkan (cegah XSS via CSS)
        $allowedStyles = [
            'font-size',
            'font-weight',
            'font-style',
            'text-decoration',
            'color',
            'background-color',
            'font-family',
            'line-height'
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
            $tag = strtolower($node->nodeName);
            if (!in_array($tag, $allowedTags)) {
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
            if (!in_array($attrName, $allowedAttrs)) {
                $attr->ownerElement->removeAttribute($attrName);
                continue;
            }
            // Filter nilai style — hanya izinkan properti yang aman
            if ($attrName === 'style') {
                $styleValue  = $attr->value;
                $parts       = explode(';', $styleValue);
                $safeParts   = [];
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    $colonPos = strpos($part, ':');
                    if ($colonPos === false) continue;
                    $prop = trim(strtolower(substr($part, 0, $colonPos)));
                    $val  = trim(substr($part, $colonPos + 1));
                    // Cegah javascript: di nilai style
                    if (stripos($val, 'javascript') !== false) continue;
                    if (stripos($val, 'expression') !== false) continue;
                    if (in_array($prop, $allowedStyles)) {
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
     * Upload PDF - Step 1
     */
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

        // Buat folder upload
        $uploadDir = __DIR__ . '/../../public/uploads/pdfs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate nama file unik
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'soal_' . time() . '_' . uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'PDF berhasil diupload',
            'file_path' => '/public/uploads/pdfs/' . $filename,
            'file_name' => $file['name']
        ]);
        exit;
    }

    public function previewPdfQuestions()
    {
        while (ob_get_level()) ob_end_clean();
        requireAuth();
        requireRole(['admin', 'guru']);
        header('Content-Type: application/json; charset=utf-8');
    
        $pdfPath = $_POST['pdf_path'] ?? '';
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $subTopic = !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null;
        $points = (int)($_POST['points'] ?? 1);
        $questionCount = (int)($_POST['question_count'] ?? 0);
        $answers = $_POST['answers'] ?? '[]';
    
        // BARU: validasi tipe soal
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
    
        if (!$questionCount || $questionCount < 1) {
            echo json_encode(['success' => false, 'message' => 'Jumlah soal tidak valid']);
            exit;
        }
    
        $fullPath = realpath(__DIR__ . '/../..' . $pdfPath);
    
        if (!$fullPath || !file_exists($fullPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'File PDF tidak ditemukan',
                'debug_path' => $fullPath,
                'pdf_path' => $pdfPath
            ]);
            exit;
        }
    
        try {
            $userId = auth()['id'];
            $fileName = basename($pdfPath);
            $savedCount = 0;
    
            $answersArray = json_decode($answers, true);
            if (!is_array($answersArray)) {
                $answersArray = [];
            }
    
            for ($i = 1; $i <= $questionCount; $i++) {
                $correctAnswer = $answersArray[$i] ?? null;
    
                // BARU: untuk isian singkat angka, pastikan nilainya numerik
                if ($type === 'short_answer' && $correctAnswer !== null) {
                    $correctAnswer = trim((string)$correctAnswer);
                    if ($correctAnswer === '' || !is_numeric($correctAnswer)) {
                        $correctAnswer = null;
                    }
                }
    
                $stmt = db()->prepare("
                    INSERT INTO questions (
                        subject_id, 
                        type, 
                        sub_topic,
                        question_number,
                        question_text, 
                        pdf_file, 
                        correct_answer,
                        points, 
                        created_by, 
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
    
                $stmt->execute([
                    $subjectId,
                    $type, // BARU: dinamis, bukan hardcode 'multiple_choice'
                    $subTopic,
                    $i,
                    'Soal PDF #' . $i . ' - ' . $fileName,
                    $pdfPath,
                    $correctAnswer,
                    $points,
                    $userId
                ]);
    
                $savedCount++;
            }
    
            echo json_encode([
                'success' => true,
                'message' => "Berhasil menyimpan {$savedCount} soal dari PDF",
                'saved' => $savedCount
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    /**
     * Tampilkan PDF di browser (inline, bukan download)
     */
    /**
     * Tampilkan PDF di browser (inline, bukan download)
     */
    public function viewPdf()
    {
        // Matikan semua output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        requireAuth();

        $file = $_GET['file'] ?? '';

        // Validasi file
        if (empty($file)) {
            http_response_code(400);
            exit('File tidak valid');
        }

        // Hanya izinkan file PDF
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'pdf') {
            http_response_code(400);
            exit('Hanya file PDF yang diizinkan');
        }

        // Bersihkan nama file dari karakter berbahaya
        $file = basename($file);

        // Path ke file
        $filePath = __DIR__ . '/../../public/uploads/pdfs/' . $file;

        // Cek file ada
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('File PDF tidak ditemukan: ' . $file);
        }

        // Cek file readable
        if (!is_readable($filePath)) {
            http_response_code(403);
            exit('File tidak dapat dibaca');
        }

        // Dapatkan ukuran file
        $fileSize = filesize($filePath);

        // Header penting untuk inline display
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/pdf; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $fileSize);
        header('Accept-Ranges: bytes');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Baca dan kirim file dalam chunk
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


    /**
     * Ekstrak teks dari PDF
     */
    private function extractTextFromPdf($filePath)
    {
        // Opsi 1: smalot/pdfparser
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
                if (!empty($text)) return $text;
            } catch (\Exception $e) {
                error_log("Smalot parser error: " . $e->getMessage());
            }
        }

        // Opsi 2: pdftotext command line
        if (function_exists('shell_exec')) {
            $output = shell_exec("pdftotext " . escapeshellarg($filePath) . " - 2>&1");
            if (!empty($output) && strpos($output, 'Error') === false) {
                return $output;
            }
        }

        // Opsi 3: Baca langsung (fallback)
        $content = file_get_contents($filePath);
        if ($content !== false) {
            return $this->extractTextFromPdfContent($content);
        }

        return '';
    }

    /**
     * Ekstrak teks dari konten PDF (fallback)
     */
    private function extractTextFromPdfContent($content)
    {
        $text = preg_replace('/obj.*?endobj/s', '', $content);
        $text = preg_replace('/stream.*?endstream/s', '', $text);
        $text = preg_replace('/<</', ' ', $text);
        $text = preg_replace('/>>/', ' ', $text);
        $text = preg_replace('/\[\]/', ' ', $text);
        $text = preg_replace('/\(\)/', ' ', $text);

        $text = preg_replace_callback('/<([0-9A-Fa-f]+)>/', function ($matches) {
            return hex2bin($matches[1]);
        }, $text);

        $text = preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function parseQuestionsFromText($text, $count)
    {
        $questions = [];
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Debug
        error_log("Parsing text: " . substr($text, 0, 200));

        // Pattern yang lebih fleksibel untuk nomor soal
        // Mendeteksi: 1., 1), 1, Soal 1, Nomor 1, dll
        $pattern = '/(?=\n?\s*(?:Soal\s*)?(\d+)[\.\)]\s+)/i';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $currentNumber = null;
        $currentQuestion = '';
        $currentOptions = [];
        $currentCorrect = null;

        // Pattern untuk pilihan jawaban - lebih fleksibel
        $optionPattern = '/^([A-E])[\.\)]\s*(.+?)(?=\n[A-E][\.\)]|\n\d+[\.\)]|\Z)/s';

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Cek apakah ini nomor soal
            if (preg_match('/^(?:Soal\s*)?(\d+)[\.\)]\s*(.*)$/s', $part, $matches)) {
                // Simpan soal sebelumnya jika ada
                if ($currentNumber !== null && !empty(trim($currentQuestion))) {
                    $questions[] = [
                        'number' => $currentNumber,
                        'question' => trim($currentQuestion),
                        'options' => $currentOptions,
                        'correct_answer' => $currentCorrect
                    ];

                    if (count($questions) >= $count) break;
                }

                $currentNumber = (int)$matches[1];
                $currentQuestion = trim($matches[2]);
                $currentOptions = [];
                $currentCorrect = null;

                error_log("Found question #{$currentNumber}: " . substr($currentQuestion, 0, 100));
            } else {
                // Cek apakah ini pilihan jawaban
                if (preg_match($optionPattern, $part, $optMatches)) {
                    $option = $optMatches[1];
                    $optionText = trim($optMatches[2]);
                    $currentOptions[$option] = $optionText;

                    // Cek jawaban benar
                    if (preg_match('/\*|\(jawaban benar\)|\(kunci\)|✓/i', $optionText)) {
                        $currentCorrect = $option;
                        $currentOptions[$option] = preg_replace('/\*|\(jawaban benar\)|\(kunci\)|✓/i', '', $optionText);
                    }
                } else {
                    // Tambahkan ke soal jika bukan pilihan
                    if (empty($currentOptions)) {
                        $currentQuestion .= "\n" . $part;
                    }
                }
            }
        }

        // Simpan soal terakhir
        if ($currentNumber !== null && !empty(trim($currentQuestion)) && count($questions) < $count) {
            $questions[] = [
                'number' => $currentNumber,
                'question' => trim($currentQuestion),
                'options' => $currentOptions,
                'correct_answer' => $currentCorrect
            ];
        }

        error_log("Total questions parsed: " . count($questions));
        return $questions;
    }
    // Tambahkan di QuestionController:

    /**
     * Preview questions from PDF
     */

    private function uploadImage($file)
    {
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            throw new \Exception('Format gambar tidak didukung');
        }
        $extension   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename    = 'q_' . time() . uniqid() . '.' . $extension;
        $destination = 'uploads/questions/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Gagal upload gambar');
        }
        return $filename;
    }

    private function uploadAudio($file)
    {
        $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt  = ['mp3', 'wav', 'ogg', 'm4a'];
        if (!in_array($ext, $allowedExt)) {
            throw new \Exception('Format audio tidak didukung. Gunakan MP3, WAV, OGG, atau M4A.');
        }
        if ($file['size'] > 20 * 1024 * 1024) {
            throw new \Exception('Ukuran file audio maksimal 20MB.');
        }
        $uploadDir = 'uploads/audio/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename    = 'audio_' . time() . uniqid() . '.' . $ext;
        $destination = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Gagal upload file audio.');
        }
        return $filename;
    }

    private function recalculateScoresForQuestion($questionId)
    {
        try {
            $examStmt = db()->prepare("SELECT DISTINCT exam_id FROM exam_questions WHERE question_id = ?");
            $examStmt->execute([$questionId]);
            $examIds = array_column($examStmt->fetchAll(), 'exam_id');
            if (empty($examIds)) return;
    
            $inExam   = implode(',', array_map('intval', $examIds));
            $sessions = db()->query("
                SELECT id AS session_id, exam_id FROM exam_sessions
                WHERE exam_id IN ($inExam) AND status IN ('submitted','graded','completed')
            ")->fetchAll();
    
            foreach ($sessions as $s) {
                $calc = db()->prepare("
                    SELECT SUM(
                        CASE
                            WHEN q.type = 'essay' THEN 0
    
                            WHEN q.type = 'short_answer'
                                 AND a.answer REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                                 AND q.correct_answer REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                                 AND CAST(a.answer AS DECIMAL(20,6)) = CAST(q.correct_answer AS DECIMAL(20,6))
                                THEN COALESCE(q.points, 1)
    
                            WHEN q.type != 'short_answer'
                                 AND LOWER(TRIM(a.answer)) = LOWER(TRIM(q.correct_answer))
                                THEN COALESCE(q.points, 1)
    
                            ELSE 0
                        END
                    ) AS nilai_baru
                    FROM answers a
                    JOIN questions q ON q.id = a.question_id
                    WHERE a.session_id = ?
                      AND q.id IN (SELECT question_id FROM exam_questions WHERE exam_id = ?)
                      AND q.type IN ('multiple_choice','listening','short_answer','short_answer_text')
                ");
                $calc->execute([$s['session_id'], $s['exam_id']]);
                $nilaiBaru = (float)($calc->fetchColumn() ?? 0);
    
                // Skor TKP (tidak berubah)
                $calcTkp = db()->prepare("
                    SELECT a.answer, q.option_scores
                    FROM answers a
                    JOIN questions q ON q.id = a.question_id
                    WHERE a.session_id = ?
                      AND q.id IN (SELECT question_id FROM exam_questions WHERE exam_id = ?)
                      AND q.type = 'tkp'
                ");
                $calcTkp->execute([$s['session_id'], $s['exam_id']]);
                foreach ($calcTkp->fetchAll() as $tkpAns) {
                    $student = strtolower(trim($tkpAns['answer'] ?? ''));
                    $scores  = json_decode($tkpAns['option_scores'] ?? '{}', true);
                    $nilaiBaru += (int)($scores[$student] ?? 0);
                }
    
                db()->prepare("UPDATE exam_sessions SET score = ? WHERE id = ?")
                    ->execute([$nilaiBaru, $s['session_id']]);
            }
        } catch (\Exception $e) {
            error_log("recalculateScoresForQuestion error: " . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /question/ocrSave — simpan 1 soal hasil OCR
    // ═══════════════════════════════════════════════════════════════
    // Method ocrSubjects - tambahkan error handling
    public function ocrSubjects()
    {
        header('Content-Type: application/json');

        try {
            // Cek apakah fungsi db() tersedia
            if (!function_exists('db')) {
                throw new \Exception('Database connection function not found');
            }

            $stmt = db()->query("SELECT id, name FROM subjects ORDER BY name ASC");
            $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'subjects' => $subjects
            ]);
        } catch (\Exception $e) {
            error_log('ocrSubjects error: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengambil data mata pelajaran: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Method ocrSave - pastikan handle image base64
    public function ocrSave()
    {
        header('Content-Type: application/json');
        $user = auth();

        if (!$user || $user['role'] === 'siswa') {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan']);
            exit;
        }

        $subjectId     = $_POST['subject_id']     ?? null;
        $questionText  = trim($_POST['question_text'] ?? '');
        $optionA       = trim($_POST['option_a'] ?? '');
        $optionB       = trim($_POST['option_b'] ?? '');
        $optionC       = trim($_POST['option_c'] ?? '');
        $optionD       = trim($_POST['option_d'] ?? '');
        $optionE       = trim($_POST['option_e'] ?? '');
        $correctAnswer = strtoupper(trim($_POST['correct_answer'] ?? ''));
        $points        = (int)($_POST['points'] ?? 1);
        $imageData     = $_POST['question_image_data'] ?? null;

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
        if ($points < 1) $points = 1;

        // Handle image upload dari base64
        $questionImage = null;
        if ($imageData && strpos($imageData, 'data:image') === 0) {
            try {
                $dataLength = strlen($imageData);
                if ($dataLength > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'Ukuran gambar terlalu besar (max 5MB)']);
                    exit;
                }

                list($type, $imageData) = explode(';', $imageData);
                list(, $imageData) = explode(',', $imageData);
                $imageData = base64_decode($imageData);

                if ($imageData === false) {
                    throw new \Exception('Gagal decode gambar');
                }

                $filename = 'q_' . time() . uniqid() . '.png';
                $filepath = 'uploads/questions/' . $filename;

                if (!is_dir('uploads/questions')) {
                    mkdir('uploads/questions', 0755, true);
                }

                if (file_put_contents($filepath, $imageData)) {
                    $questionImage = $filename;
                } else {
                    throw new \Exception('Gagal menyimpan gambar');
                }
            } catch (\Exception $e) {
                error_log('Image upload error: ' . $e->getMessage());
                // Lanjutkan tanpa gambar jika gagal
                $questionImage = null;
            }
        }

        try {
            $stmt = db()->prepare("
            INSERT INTO questions
            (subject_id, type, question_text, question_image, option_a, option_b, option_c, option_d, option_e,
             correct_answer, points, created_by, created_at)
            VALUES
            (?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
            $stmt->execute([
                $subjectId,
                $questionText,
                $questionImage,
                $optionA ?: null,
                $optionB ?: null,
                $optionC ?: null,
                $optionD ?: null,
                $optionE ?: null,
                $correctAnswer,
                $points,
                $user['id']
            ]);

            echo json_encode(['success' => true, 'id' => db()->lastInsertId(), 'message' => 'Soal tersimpan']);
        } catch (\Exception $e) {
            error_log('Save question error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Gagal simpan: ' . $e->getMessage()]);
        }
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /question/ocrSaveBatch — simpan banyak soal sekaligus
    // ═══════════════════════════════════════════════════════════════
    public function ocrSaveBatch()
    {
        header('Content-Type: application/json');
        $user = auth();

        if (!$user || $user['role'] === 'siswa') {
            echo json_encode(['success' => false, 'message' => 'Tidak diizinkan']);
            exit;
        }

        $rawQuestions = $_POST['questions'] ?? '[]';
        $questions = json_decode($rawQuestions, true);

        if (!is_array($questions) || empty($questions)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada soal yang disimpan']);
            exit;
        }

        $savedCount = 0;
        $errors = [];

        foreach ($questions as $index => $q) {
            try {
                $subjectId     = $q['subject_id'] ?? null;
                $questionText  = trim($q['question_text'] ?? '');
                $optionA       = trim($q['option_a'] ?? '');
                $optionB       = trim($q['option_b'] ?? '');
                $optionC       = trim($q['option_c'] ?? '');
                $optionD       = trim($q['option_d'] ?? '');
                $optionE       = trim($q['option_e'] ?? '');
                $correctAnswer = strtoupper(trim($q['correct_answer'] ?? ''));
                $points        = (int)($q['points'] ?? 1);
                $imageData     = $q['image_data'] ?? null;

                if (!$subjectId || $questionText === '' || !in_array($correctAnswer, ['A', 'B', 'C', 'D', 'E'])) {
                    $errors[] = "Soal #" . ($index + 1) . ": Data tidak valid";
                    continue;
                }

                // Handle image
                $questionImage = null;
                if ($imageData && strpos($imageData, 'data:image') === 0) {
                    list($type, $imageData) = explode(';', $imageData);
                    list(, $imageData) = explode(',', $imageData);
                    $imageData = base64_decode($imageData);

                    if ($imageData !== false) {
                        $filename = 'q_' . time() . uniqid() . '.png';
                        $filepath = 'uploads/questions/' . $filename;

                        if (!is_dir('uploads/questions')) {
                            mkdir('uploads/questions', 0755, true);
                        }

                        if (file_put_contents($filepath, $imageData)) {
                            $questionImage = $filename;
                        }
                    }
                }

                $stmt = db()->prepare("
                INSERT INTO questions
                (subject_id, type, question_text, question_image, option_a, option_b, option_c, option_d, option_e,
                 correct_answer, points, created_by, created_at)
                VALUES
                (?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
                $stmt->execute([
                    $subjectId,
                    $questionText,
                    $questionImage,
                    $optionA ?: null,
                    $optionB ?: null,
                    $optionC ?: null,
                    $optionD ?: null,
                    $optionE ?: null,
                    $correctAnswer,
                    $points,
                    $user['id']
                ]);
                $savedCount++;
            } catch (\Exception $e) {
                $errors[] = "Soal #" . ($index + 1) . ": " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true,
            'saved' => $savedCount,
            'total' => count($questions),
            'errors' => $errors,
            'message' => "Berhasil menyimpan $savedCount dari " . count($questions) . " soal"
        ]);
        exit;
    }
}